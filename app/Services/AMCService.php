<?php

namespace App\Services;

use App\Models\MonthlyConsumptionItem;
use App\Models\MonthlyConsumptionReport;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class AMCService
{
    const SCREENING_THRESHOLD = 70.0; // 70% threshold

    /**
     * Calculate screened AMC for a specific product at a facility
     * 
     * @param int $facilityId
     * @param int $productId
     * @param int $monthsToAnalyze
     * @return array
     */
    public function calculateScreenedAMC(int $facilityId, int $productId, int $monthsToAnalyze = 12): array
    {
        // Get monthly consumption data (last 12 months, excluding zeros)
        $monthlyItems = MonthlyConsumptionItem::whereHas('report', function ($query) use ($facilityId) {
            $query->where('facility_id', $facilityId);
        })
        ->where('product_id', $productId)
        ->where('quantity', '>', 0) // Exclude zero quantities
        ->with(['report'])
        ->get();

        // Convert to array for processing (newest first)
        $monthsData = $monthlyItems->sortByDesc(function ($item) {
            return $item->report->month_year;
        })->take(12)->map(function ($item) {
            return [
                'month' => $item->report->month_year,
                'consumption' => (float) $item->quantity
            ];
        })->values()->toArray();

        // Exclude current month
        $excludeMonth = Carbon::now()->format('Y-m');
        $monthsData = array_filter($monthsData, function ($month) use ($excludeMonth) {
            return $month['month'] !== $excludeMonth;
        });
        $monthsData = array_values($monthsData); // Re-index array

        // Use warehouse algorithm for AMC calculation
        $result = $this->processAmcCalculation($monthsData, $productId);
        
        return [
            'amc' => $result['amc'],
            'max_amc' => $result['max_amc'],
            'total_months_analyzed' => count($monthsData),
            'eligible_months_count' => count($result['selectedMonths']),
            'excluded_months_count' => count($monthsData) - count($result['selectedMonths']),
            'screening_details' => [$result['calculation']],
            'months_breakdown' => $result['selectedMonths']
        ];
    }

    /**
     * Process AMC calculation using warehouse algorithm (3-month group screening)
     * 
     * @param array $monthsData
     * @param int $productId
     * @return array
     */
    private function processAmcCalculation($monthsData, $productId)
    {
        $monthsCount = count($monthsData);

        if ($monthsCount === 0) {
            return [
                'amc' => 0,
                'max_amc' => 0,
                'selectedMonths' => [],
                'totalMonths' => 0,
                'calculation' => 'No consumption data available'
            ];
        }

        // Apply AMC screening logic
        $selectedMonths = [];
        $amc = 0;
        $calculation = '';

        if ($monthsCount >= 3) {
            // Step 1: Take closest 3 months (first 3 in our desc-sorted array)
            $firstThreeMonths = array_slice($monthsData, 0, 3);
            
            // Step 2: Calculate average of first 3 months
            $sum = array_sum(array_column($firstThreeMonths, 'consumption'));
            $average = $sum / 3;
            
            // Step 3: Screen each month for 70% deviation
            $passedMonths = [];
            
            foreach ($firstThreeMonths as $month) {
                $quantity = $month['consumption'];
                $deviation = abs($average - $quantity);
                $percentage = $average > 0 ? ($deviation / $average) * 100 : 0;
                
                if ($percentage <= 70) {
                    $passedMonths[] = $month;
                }
            }
            
            // Step 4: If all 3 months passed, use them
            if (count($passedMonths) === 3) {
                $selectedMonths = $firstThreeMonths;
                $amc = $average;
                $calculation = "All 3 closest months passed 70% screening. AMC = {$amc}";
            } else {
                // Step 5: Reselect 3 months including passed ones
                // Get remaining months to combine with passed ones
                $remainingMonths = array_slice($monthsData, 3);
                
                // Build new group: passed months + enough remaining months to make 3
                $newGroup = $passedMonths;
                $remainingIndex = 0;
                
                // Add months from remaining until we have 3 total
                while (count($newGroup) < 3 && $remainingIndex < count($remainingMonths)) {
                    $newGroup[] = $remainingMonths[$remainingIndex];
                    $remainingIndex++;
                }
                
                // If we still don't have 3, fall back to original 3
                if (count($newGroup) < 3) {
                    $selectedMonths = $firstThreeMonths;
                    $amc = $average;
                    $calculation = "Fallback to first 3 months (insufficient data for reselection). AMC = {$amc}";
                } else {
                    // Calculate new average
                    $newSum = array_sum(array_column($newGroup, 'consumption'));
                    $newAverage = $newSum / 3;
                    
                    // Screen the new group - only screen the newly added months
                    // (passed months are already verified)
                    $allValid = true;
                    $passedCount = count($passedMonths);
                    
                    for ($i = $passedCount; $i < count($newGroup); $i++) {
                        $month = $newGroup[$i];
                        $quantity = $month['consumption'];
                        $deviation = abs($newAverage - $quantity);
                        $percentage = $newAverage > 0 ? ($deviation / $newAverage) * 100 : 0;
                        
                        if ($percentage > 70) {
                            $allValid = false;
                            break;
                        }
                    }
                    
                    if ($allValid) {
                        $selectedMonths = $newGroup;
                        $amc = $newAverage;
                        $calculation = "Reselected group with {$passedCount} passed months + " . (3 - $passedCount) . " additional months. AMC = {$amc}";
                    } else {
                        // Final fallback to first 3 months
                        $selectedMonths = $firstThreeMonths;
                        $amc = $average;
                        $calculation = "Reselected group failed screening, using first 3 months. AMC = {$amc}";
                    }
                }
            }
        } elseif ($monthsCount === 2) {
            // If only 2 months available, use both
            $selectedMonths = $monthsData;
            $sum = array_sum(array_column($selectedMonths, 'consumption'));
            $amc = $sum / 2;
            $calculation = "Only 2 months available, no screening applied. AMC = {$amc}";
        } elseif ($monthsCount === 1) {
            // If only 1 month available, use it
            $selectedMonths = $monthsData;
            $amc = $selectedMonths[0]['consumption'];
            $calculation = "Only 1 month available, no screening applied. AMC = {$amc}";
        }

        // Calculate max AMC from selected months
        $maxAmc = 0;
        if (!empty($selectedMonths)) {
            $maxAmc = max(array_column($selectedMonths, 'consumption'));
        }

        return [
            'amc' => round($amc, 2),
            'max_amc' => round($maxAmc, 2),
            'selectedMonths' => $selectedMonths,
            'totalMonths' => $monthsCount,
            'calculation' => $calculation
        ];
    }

    /**
     * Apply AMC screening logic month by month (OLD METHOD - DEPRECATED)
     * 
     * @param Collection $monthlyItems
     * @return array
     */
    private function applyScreeningLogic(Collection $monthlyItems): array
    {
        $eligibleMonths = collect();
        $excludedMonths = collect();
        $details = [];
        $monthsBreakdown = [];

        // Sort by month_year ascending (oldest first) for sequential processing
        $sortedItems = $monthlyItems->sortBy(function ($item) {
            return $item->report->month_year;
        });

        foreach ($sortedItems as $index => $currentItem) {
            $monthYear = $currentItem->report->month_year;
            $currentQuantity = $currentItem->quantity;
            
            // For first 3 months, automatically include (no screening possible)
            if ($index < 3) {
                $eligibleMonths->push($currentItem);
                $monthsBreakdown[] = [
                    'month_year' => $monthYear,
                    'quantity' => $currentQuantity,
                    'is_eligible' => true,
                    'reason' => 'First 3 months - no screening applied',
                    'deviation_percentage' => null,
                    'average_of_previous_3' => null,
                    'color_code' => 'green'
                ];
                $details[] = "✅ {$monthYear}: {$currentQuantity} units - Included (first 3 months)";
                continue;
            }

            // Get the previous 3 months data (not just eligible ones, but all previous)
            $previousThreeMonths = $sortedItems->slice($index - 3, 3);
            $averageConsumption = $previousThreeMonths->avg('quantity');
            
            // Calculate percentage difference
            $deviationPercentage = $this->calculateDeviationPercentage($currentQuantity, $averageConsumption);
            
            // Apply screening threshold
            if ($deviationPercentage <= self::SCREENING_THRESHOLD) {
                // Include this month
                $eligibleMonths->push($currentItem);
                $monthsBreakdown[] = [
                    'month_year' => $monthYear,
                    'quantity' => $currentQuantity,
                    'is_eligible' => true,
                    'deviation_percentage' => $deviationPercentage,
                    'average_of_previous_3' => round($averageConsumption, 2),
                    'reason' => "Deviation {$deviationPercentage}% ≤ 70%",
                    'color_code' => 'yellow'
                ];
                $details[] = "✅ {$monthYear}: {$currentQuantity} units - Included (deviation: {$deviationPercentage}%)";
            } else {
                // Exclude this month - use most recent previous eligible month instead
                $mostRecentEligible = $eligibleMonths->last();
                if ($mostRecentEligible) {
                    $eligibleMonths->push($mostRecentEligible); // Add duplicate of most recent eligible
                }
                
                $excludedMonths->push($currentItem);
                $monthsBreakdown[] = [
                    'month_year' => $monthYear,
                    'quantity' => $currentQuantity,
                    'is_eligible' => false,
                    'deviation_percentage' => $deviationPercentage,
                    'average_of_previous_3' => round($averageConsumption, 2),
                    'reason' => "Deviation {$deviationPercentage}% > 70% - replaced with most recent eligible",
                    'replacement_month' => $mostRecentEligible ? $mostRecentEligible->report->month_year : null,
                    'replacement_quantity' => $mostRecentEligible ? $mostRecentEligible->quantity : null,
                    'color_code' => 'red'
                ];
                $details[] = "❌ {$monthYear}: {$currentQuantity} units - Excluded (deviation: {$deviationPercentage}%), replaced with {$mostRecentEligible->report->month_year}: {$mostRecentEligible->quantity} units";
            }
        }

        return [
            'eligibleMonths' => $eligibleMonths,
            'excludedMonths' => $excludedMonths,
            'details' => $details,
            'monthsBreakdown' => $monthsBreakdown
        ];
    }

    /**
     * Calculate percentage deviation
     * 
     * @param float $current
     * @param float $average
     * @return float
     */
    private function calculateDeviationPercentage(float $current, float $average): float
    {
        if ($average == 0) {
            return $current > 0 ? 100.0 : 0.0;
        }
        
        return round(abs($current - $average) / $average * 100, 2);
    }

    /**
     * Calculate final AMC from eligible months
     * 
     * @param Collection $eligibleMonths
     * @return float
     */
    private function calculateFinalAMC(Collection $eligibleMonths): float
    {
        if ($eligibleMonths->isEmpty()) {
            return 0.0;
        }

        $eligibleCount = $eligibleMonths->count();
        
        if ($eligibleCount >= 3) {
            // Use last 3 eligible months for AMC calculation
            $lastThree = $eligibleMonths->take(-3);
            return round($lastThree->avg('quantity'), 2);
        } elseif ($eligibleCount == 2) {
            // If only 2 months are eligible, AMC = sum of 2 / 2
            return round($eligibleMonths->avg('quantity'), 2);
        } else {
            // If only 1 month is eligible, AMC = that 1 month's quantity
            return round($eligibleMonths->first()->quantity, 2);
        }
    }

    /**
     * Get monthly consumption data for screening
     * 
     * @param int $facilityId
     * @param int $productId
     * @param int $monthsToAnalyze
     * @return Collection
     */
    private function getMonthlyConsumptionData(int $facilityId, int $productId, int $monthsToAnalyze): Collection
    {
        // Get last N months (excluding current month)
        $months = [];
        for ($i = 1; $i <= $monthsToAnalyze; $i++) {
            $months[] = Carbon::now()->subMonths($i)->format('Y-m');
        }

        return MonthlyConsumptionItem::whereHas('report', function ($query) use ($facilityId, $months) {
            $query->where('facility_id', $facilityId)
                  ->whereIn('month_year', $months);
        })
        ->where('product_id', $productId)
        ->with(['report', 'product'])
        ->get();
    }

    /**
     * Handle cases with insufficient data (less than 4 months)
     * 
     * @param Collection $monthlyItems
     * @return array
     */
    private function handleInsufficientData(Collection $monthlyItems): array
    {
        $amc = $monthlyItems->isEmpty() ? 0.0 : round($monthlyItems->avg('quantity'), 2);
        
        return [
            'amc' => $amc,
            'total_months_analyzed' => $monthlyItems->count(),
            'eligible_months_count' => $monthlyItems->count(),
            'excluded_months_count' => 0,
            'screening_details' => ['Insufficient data for screening (need at least 4 months for screening to begin)'],
            'months_breakdown' => $monthlyItems->map(function ($item) {
                return [
                    'month_year' => $item->report->month_year,
                    'quantity' => $item->quantity,
                    'is_eligible' => true,
                    'reason' => 'Insufficient data for screening',
                    'color_code' => 'green'
                ];
            })->toArray()
        ];
    }

    /**
     * Get AMC summary for reporting
     * 
     * @param int $facilityId
     * @param int $productId
     * @return array
     */
    public function getAMCSummary(int $facilityId, int $productId): array
    {
        $result = $this->calculateScreenedAMC($facilityId, $productId);
        
        return [
            'amc_value' => $result['amc'],
            'calculation_method' => $this->getCalculationMethod($result['eligible_months_count']),
            'reliability_score' => $this->calculateReliabilityScore($result),
            'recommendation' => $this->getRecommendation($result)
        ];
    }

    /**
     * Get calculation method description
     * 
     * @param int $validMonthsCount
     * @return string
     */
    private function getCalculationMethod(int $eligibleMonthsCount): string
    {
        if ($eligibleMonthsCount >= 3) {
            return "Average of last 3 eligible months";
        } elseif ($eligibleMonthsCount == 2) {
            return "Average of 2 eligible months";
        } elseif ($eligibleMonthsCount == 1) {
            return "Single month value";
        } else {
            return "No eligible data available";
        }
    }

    /**
     * Calculate reliability score
     * 
     * @param array $result
     * @return string
     */
    private function calculateReliabilityScore(array $result): string
    {
        $eligibleRatio = $result['total_months_analyzed'] > 0 
            ? $result['eligible_months_count'] / $result['total_months_analyzed'] 
            : 0;

        if ($eligibleRatio >= 0.8) return "High";
        if ($eligibleRatio >= 0.6) return "Medium";
        return "Low";
    }

    /**
     * Get recommendation based on AMC results
     * 
     * @param array $result
     * @return string
     */
    private function getRecommendation(array $result): string
    {
        if ($result['excluded_months_count'] > $result['eligible_months_count']) {
            return "High variability detected. Consider investigating consumption patterns.";
        }
        if ($result['eligible_months_count'] < 3) {
            return "Limited historical data. Monitor and collect more data for better accuracy.";
        }
        return "AMC calculation is reliable based on historical data.";
    }
}