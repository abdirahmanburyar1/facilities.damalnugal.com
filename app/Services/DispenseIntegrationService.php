<?php

namespace App\Services;

use App\Models\Dispence;
use App\Models\DispenceItem;
use App\Models\MohDispense;
use App\Models\MohDispenseItem;
use App\Models\MonthlyConsumptionReport;
use App\Models\MonthlyConsumptionItem;
use App\Models\Facility;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DispenseIntegrationService
{
    /**
     * Sync dispense data to monthly consumption reports
     * This method aggregates both Dispence and MohDispense data into monthly consumption
     */
    public function syncDispenseToMonthlyConsumption($facilityId, $monthYear = null)
    {
        if (!$monthYear) {
            $monthYear = Carbon::now()->format('Y-m');
        }

        try {
            DB::beginTransaction();

            // Get or create monthly consumption report for the facility and month
            $monthlyReport = MonthlyConsumptionReport::firstOrCreate([
                'facility_id' => $facilityId,
                'month_year' => $monthYear,
            ], [
                'status' => 'draft',
                'created_by' => auth()->id(),
            ]);

            // Aggregate Dispence data (patient-level dispensing)
            $dispenceData = $this->aggregateDispenceData($facilityId, $monthYear);
            
            // Aggregate MohDispense data (inventory-level dispensing)
            $mohDispenseData = $this->aggregateMohDispenseData($facilityId, $monthYear);
            
            // Merge and consolidate the data
            $consolidatedData = $this->consolidateDispenseData($dispenceData, $mohDispenseData);
            
            // Update or create monthly consumption items
            $this->updateMonthlyConsumptionItems($monthlyReport->id, $consolidatedData);

            DB::commit();
            
            Log::info('Dispense data synced to monthly consumption', [
                'facility_id' => $facilityId,
                'month_year' => $monthYear,
                'dispence_records' => count($dispenceData),
                'moh_dispense_records' => count($mohDispenseData),
                'consolidated_records' => count($consolidatedData)
            ]);

            return [
                'success' => true,
                'message' => 'Dispense data synced successfully',
                'data' => [
                    'monthly_report_id' => $monthlyReport->id,
                    'dispence_count' => count($dispenceData),
                    'moh_dispense_count' => count($mohDispenseData),
                    'consolidated_count' => count($consolidatedData)
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error syncing dispense data to monthly consumption', [
                'facility_id' => $facilityId,
                'month_year' => $monthYear,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error syncing dispense data: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Aggregate Dispence data by product for the given month
     */
    private function aggregateDispenceData($facilityId, $monthYear)
    {
        return DispenceItem::join('dispences', 'dispence_items.dispence_id', '=', 'dispences.id')
            ->where('dispences.facility_id', $facilityId)
            ->whereYear('dispences.dispence_date', Carbon::parse($monthYear)->year)
            ->whereMonth('dispences.dispence_date', Carbon::parse($monthYear)->month)
            ->select(
                'dispence_items.product_id',
                DB::raw('SUM(dispence_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT dispences.id) as dispense_count'),
                DB::raw('"patient_dispense" as source_type')
            )
            ->groupBy('dispence_items.product_id')
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Aggregate MohDispense data by product for the given month
     */
    private function aggregateMohDispenseData($facilityId, $monthYear)
    {
        return MohDispenseItem::join('moh_dispenses', 'moh_dispense_items.moh_dispense_id', '=', 'moh_dispenses.id')
            ->where('moh_dispenses.facility_id', $facilityId)
            ->whereYear('moh_dispense_items.dispense_date', Carbon::parse($monthYear)->year)
            ->whereMonth('moh_dispense_items.dispense_date', Carbon::parse($monthYear)->month)
            ->select(
                'moh_dispense_items.product_id',
                DB::raw('SUM(moh_dispense_items.quantity) as total_quantity'),
                DB::raw('COUNT(DISTINCT moh_dispenses.id) as dispense_count'),
                DB::raw('"inventory_dispense" as source_type')
            )
            ->groupBy('moh_dispense_items.product_id')
            ->get()
            ->keyBy('product_id');
    }

    /**
     * Consolidate dispense data from both sources
     */
    private function consolidateDispenseData($dispenceData, $mohDispenseData)
    {
        $consolidated = [];

        // Process Dispence data
        foreach ($dispenceData as $productId => $data) {
            $consolidated[$productId] = [
                'product_id' => $productId,
                'patient_dispense_quantity' => $data->total_quantity,
                'patient_dispense_count' => $data->dispense_count,
                'inventory_dispense_quantity' => 0,
                'inventory_dispense_count' => 0,
                'total_quantity' => $data->total_quantity,
                'total_dispense_count' => $data->dispense_count,
                'sources' => ['patient_dispense']
            ];
        }

        // Process MohDispense data and merge
        foreach ($mohDispenseData as $productId => $data) {
            if (isset($consolidated[$productId])) {
                // Merge with existing data
                $consolidated[$productId]['inventory_dispense_quantity'] = $data->total_quantity;
                $consolidated[$productId]['inventory_dispense_count'] = $data->dispense_count;
                $consolidated[$productId]['total_quantity'] += $data->total_quantity;
                $consolidated[$productId]['total_dispense_count'] += $data->dispense_count;
                $consolidated[$productId]['sources'][] = 'inventory_dispense';
            } else {
                // Create new entry
                $consolidated[$productId] = [
                    'product_id' => $productId,
                    'patient_dispense_quantity' => 0,
                    'patient_dispense_count' => 0,
                    'inventory_dispense_quantity' => $data->total_quantity,
                    'inventory_dispense_count' => $data->dispense_count,
                    'total_quantity' => $data->total_quantity,
                    'total_dispense_count' => $data->dispense_count,
                    'sources' => ['inventory_dispense']
                ];
            }
        }

        return $consolidated;
    }

    /**
     * Update monthly consumption items with consolidated data
     */
    private function updateMonthlyConsumptionItems($monthlyReportId, $consolidatedData)
    {
        foreach ($consolidatedData as $productId => $data) {
            MonthlyConsumptionItem::updateOrCreate([
                'parent_id' => $monthlyReportId,
                'product_id' => $productId,
            ], [
                'quantity' => $data['total_quantity'],
                'patient_dispense_quantity' => $data['patient_dispense_quantity'],
                'inventory_dispense_quantity' => $data['inventory_dispense_quantity'],
                'patient_dispense_count' => $data['patient_dispense_count'],
                'inventory_dispense_count' => $data['inventory_dispense_count'],
                'total_dispense_count' => $data['total_dispense_count'],
                'sources' => json_encode($data['sources']),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Get comprehensive dispense statistics for a facility
     */
    public function getDispenseStatistics($facilityId, $monthYear = null)
    {
        if (!$monthYear) {
            $monthYear = Carbon::now()->format('Y-m');
        }

        $dispenceStats = Dispence::where('facility_id', $facilityId)
            ->whereYear('dispence_date', Carbon::parse($monthYear)->year)
            ->whereMonth('dispence_date', Carbon::parse($monthYear)->month)
            ->withCount('items')
            ->get();

        $mohDispenseStats = MohDispense::where('facility_id', $facilityId)
            ->whereYear('created_at', Carbon::parse($monthYear)->year)
            ->whereMonth('created_at', Carbon::parse($monthYear)->month)
            ->withCount('items')
            ->get();

        return [
            'month_year' => $monthYear,
            'dispence' => [
                'total_dispenses' => $dispenceStats->count(),
                'total_items' => $dispenceStats->sum('items_count'),
                'unique_patients' => $dispenceStats->count(),
            ],
            'moh_dispense' => [
                'total_dispenses' => $mohDispenseStats->count(),
                'total_items' => $mohDispenseStats->sum('items_count'),
            ],
            'combined' => [
                'total_dispenses' => $dispenceStats->count() + $mohDispenseStats->count(),
                'total_items' => $dispenceStats->sum('items_count') + $mohDispenseStats->sum('items_count'),
            ]
        ];
    }

    /**
     * Sync all facilities' dispense data for a given month
     */
    public function syncAllFacilitiesDispenseData($monthYear = null)
    {
        $facilities = Facility::all();
        $results = [];

        foreach ($facilities as $facility) {
            $result = $this->syncDispenseToMonthlyConsumption($facility->id, $monthYear);
            $results[$facility->id] = $result;
        }

        return $results;
    }
}
