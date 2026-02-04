<?php

namespace App\Jobs;

use App\Models\Facility;
use App\Models\Product;
use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Models\FacilityInventoryMovement;
use App\Models\FacilityInventory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class GenerateMonthlyInventoryReportJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of seconds the job can run before timing out.
     * Set to 2 hours for large facilities with many products
     */
    public $timeout = 7200;

    /**
     * The maximum number of unhandled exceptions to allow before failing.
     */
    public $maxExceptions = 3;

    /**
     * The number of times the job may be attempted.
     */
    public $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     */
    public $backoff = [60, 300, 900]; // 1 min, 5 min, 15 min

    /**
     * Indicate if the job should be marked as failed on timeout.
     */
    public $failOnTimeout = true;

    /**
     * The unique ID of the job.
     * This prevents multiple reports for the same facility/period from running simultaneously
     */
    public function uniqueId(): string
    {
        return "monthly-report-{$this->facilityId}-{$this->reportPeriod}";
    }

    /**
     * The number of seconds after which the job's unique lock will be released.
     */
    public $uniqueFor = 7200; // 2 hours

    protected $facilityId;
    protected $reportPeriod;
    protected $force;

    /**
     * Create a new job instance.
     */
    public function __construct($facilityId = null, $reportPeriod = null, $force = false)
    {
        $this->facilityId = $facilityId;
        $this->reportPeriod = $reportPeriod ?? now()->format('Y-m');
        $this->force = $force;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Set memory limit for large datasets
        ini_set('memory_limit', '1024M');

        try {
            DB::transaction(function () {
                $facilities = $this->facilityId ? 
                    Facility::where('id', $this->facilityId)->get() : 
                    Facility::where('is_active', true)->get();

                foreach ($facilities as $facility) {
                    $this->generateReportForFacility($facility);
                    
                    // Force garbage collection after each facility to manage memory
                    if (function_exists('gc_collect_cycles')) {
                        gc_collect_cycles();
                    }
                    
                }
            });
            
        } catch (\Exception $e) {
            
            throw $e;
        }
    }

    /**
     * Generate report for a specific facility
     */
    private function generateReportForFacility(Facility $facility): void
    {

        // Check if report already exists
        $existingReport = FacilityMonthlyReport::where([
            'facility_id' => $facility->id,
            'report_period' => $this->reportPeriod,
        ])->first();

        if ($existingReport && !$this->force) {
            echo "Report already exists for facility {$facility->id} and period {$this->reportPeriod}, skipping\n";
            return;
        }

        if ($existingReport && $this->force) {
            echo "Force mode: Deleting existing report for facility {$facility->id} and period {$this->reportPeriod}\n";
            $existingReport->items()->delete();
            $existingReport->delete();
        }

        // Create the main report record
        $report = FacilityMonthlyReport::create([
            'facility_id' => $facility->id,
            'report_period' => $this->reportPeriod,
            'status' => 'draft',
            'created_by' => 1, // System user ID for automated reports
        ]);

        echo "Created main report record for facility {$facility->id}, report ID: {$report->id}\n";

        // Generate report items
        $this->generateReportItems($facility, $report);
    }

    /**
     * Generate report items from facility inventory movements with detailed quantity breakdowns
     */
    private function generateReportItems(Facility $facility, FacilityMonthlyReport $report): void
    {
        // Define the date range for the report month
        $startDate = Carbon::createFromFormat('Y-m', $this->reportPeriod)->startOfMonth();
        $endDate = Carbon::createFromFormat('Y-m', $this->reportPeriod)->endOfMonth();

        echo "Generating report for facility {$facility->id} for period {$this->reportPeriod}\n";
        echo "Date range: {$startDate} to {$endDate}\n";

        // Get all products that have current inventory at this facility
        $inventoryProducts = DB::table('facility_inventories')
            ->select('product_id')
            ->where('facility_id', $facility->id)
            ->where('quantity', '>', 0)
            ->groupBy('product_id')
            ->pluck('product_id');

        echo "Found {$inventoryProducts->count()} products with current inventory\n";

        // Get detailed movement data for the reporting period with all quantities
        $detailedMovementData = FacilityInventoryMovement::select([
                'product_id',
                'facility_received_quantity',
                'facility_issued_quantity',
                'created_at',
                'movement_type',
                'reference_type',
                'reference_id',
                'batch_number',
                'expiry_date',
                'unit_cost',
                'total_cost'
            ])
            ->where('facility_id', $facility->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('product_id')
            ->orderBy('created_at')
            ->get();

        echo "Found {$detailedMovementData->count()} individual movements in period\n";

        // Get aggregated movement data for totals
        $aggregatedMovementData = FacilityInventoryMovement::select([
                'product_id',
                DB::raw('SUM(COALESCE(facility_received_quantity, 0)) as total_received'),
                DB::raw('SUM(COALESCE(facility_issued_quantity, 0)) as total_issued'),
                DB::raw('COUNT(*) as movement_count'),
                DB::raw('COUNT(CASE WHEN facility_received_quantity > 0 THEN 1 END) as received_transactions'),
                DB::raw('COUNT(CASE WHEN facility_issued_quantity > 0 THEN 1 END) as issued_transactions')
            ])
            ->where('facility_id', $facility->id)
            ->whereBetween('created_at', [$startDate, $endDate])
            ->groupBy('product_id')
            ->get();

        echo "Found {$aggregatedMovementData->count()} products with movements in period\n";

        // Get all unique products (from both inventory and movements)
        $allProductIds = $inventoryProducts->merge($aggregatedMovementData->pluck('product_id'))->unique();
        
        echo "Total unique products to process: {$allProductIds->count()}\n";

        // Create lookup arrays
        $aggregatedLookup = $aggregatedMovementData->keyBy('product_id');
        $detailedLookup = $detailedMovementData->groupBy('product_id');

        // Process in chunks to manage memory usage
        $chunkSize = 100;
        $totalProcessed = 0;
        $itemsCreated = 0;

        // Process all products in chunks
        $allProductIds->chunk($chunkSize)->each(function ($productChunk, $chunkIndex) use ($facility, $report, $aggregatedLookup, $detailedLookup, &$totalProcessed, &$itemsCreated) {

            foreach ($productChunk as $productId) {
                try {
                    // Get product information
                    $product = Product::select(['id', 'name', 'category_id'])->find($productId);
                    if (!$product) {
                        echo "Product {$productId} not found, skipping\n";
                        continue;
                    }

                    // Get aggregated movement data for this product (if any)
                    $aggregatedMovement = $aggregatedLookup->get($productId);
                    
                    // Get detailed movements for this product
                    $detailedMovements = $detailedLookup->get($productId, collect());

                    // Calculate opening balance
                    $openingBalance = $this->calculateOpeningBalance($facility, $product);

                    // Get the aggregated quantities (default to 0 if no movements)
                    $stockReceived = $aggregatedMovement ? (float) $aggregatedMovement->total_received : 0.0;
                    $stockIssued = $aggregatedMovement ? (float) $aggregatedMovement->total_issued : 0.0;

                    // Set default adjustments (can be manually updated later)
                    $positiveAdjustments = 0.0;
                    $negativeAdjustments = 0.0;

                    // Calculate closing balance
                    $closingBalance = $openingBalance + $stockReceived - $stockIssued + $positiveAdjustments - $negativeAdjustments;

                    // Calculate stockout days
                    $stockoutDays = $this->calculateStockoutDays($facility, $product);

                    // Prepare detailed quantity breakdown
                    $quantityBreakdown = $this->prepareQuantityBreakdown($detailedMovements, $product);

                    echo "Processing product {$product->id} ({$product->name}): Opening={$openingBalance}, Received={$stockReceived}, Issued={$stockIssued}, Closing={$closingBalance}\n";
                    echo "  - Detailed movements: {$detailedMovements->count()} transactions\n";
                    echo "  - Quantity breakdown: " . json_encode($quantityBreakdown) . "\n";

                    // Create the report item with detailed breakdown
                    FacilityMonthlyReportItem::create([
                        'parent_id' => $report->id,
                        'product_id' => $product->id,
                        'opening_balance' => $openingBalance,
                        'stock_received' => $stockReceived,
                        'stock_issued' => $stockIssued,
                        'positive_adjustments' => $positiveAdjustments,
                        'negative_adjustments' => $negativeAdjustments,
                        'closing_balance' => $closingBalance,
                        'stockout_days' => $stockoutDays,
                        'quantity_breakdown' => json_encode($quantityBreakdown), // Store detailed breakdown
                        'movement_count' => $aggregatedMovement ? $aggregatedMovement->movement_count : 0,
                        'received_transactions' => $aggregatedMovement ? $aggregatedMovement->received_transactions : 0,
                        'issued_transactions' => $aggregatedMovement ? $aggregatedMovement->issued_transactions : 0,
                    ]);

                    $itemsCreated++;
                    $totalProcessed++;

                    echo "Created report item {$itemsCreated} for product {$product->name}\n";

                } catch (\Exception $e) {
                    echo "Error creating report item for product {$productId}: " . $e->getMessage() . "\n";
                    continue;
                }
            }
        });

        echo "Completed generating {$itemsCreated} report items for facility {$facility->id}\n";
    }

    /**
     * Prepare detailed quantity breakdown for a product
     */
    private function prepareQuantityBreakdown($movements, $product): array
    {
        $breakdown = [
            'total_movements' => $movements->count(),
            'received_breakdown' => [],
            'issued_breakdown' => [],
            'by_batch' => [],
            'by_movement_type' => [],
            'by_reference_type' => [],
            'daily_totals' => [],
            'cost_analysis' => [
                'total_received_cost' => 0,
                'total_issued_cost' => 0,
                'average_unit_cost' => 0,
            ]
        ];

        $totalReceivedCost = 0;
        $totalIssuedCost = 0;
        $totalUnits = 0;

        foreach ($movements as $movement) {
            $date = $movement->created_at->format('Y-m-d');
            $batchKey = $movement->batch_number ?: 'no-batch';
            $movementType = $movement->movement_type ?: 'unknown';
            $referenceType = $movement->reference_type ?: 'unknown';

            // Daily totals
            if (!isset($breakdown['daily_totals'][$date])) {
                $breakdown['daily_totals'][$date] = [
                    'received' => 0,
                    'issued' => 0,
                    'transactions' => 0
                ];
            }

            // Received quantities
            if ($movement->facility_received_quantity > 0) {
                $receivedQty = (float) $movement->facility_received_quantity;
                $breakdown['received_breakdown'][] = [
                    'date' => $movement->created_at->format('Y-m-d H:i:s'),
                    'quantity' => $receivedQty,
                    'batch_number' => $movement->batch_number,
                    'expiry_date' => $movement->expiry_date,
                    'unit_cost' => $movement->unit_cost,
                    'total_cost' => $movement->total_cost,
                    'movement_type' => $movement->movement_type,
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id
                ];

                $breakdown['daily_totals'][$date]['received'] += $receivedQty;
                $totalReceivedCost += (float) ($movement->total_cost ?: 0);
                $totalUnits += $receivedQty;
            }

            // Issued quantities
            if ($movement->facility_issued_quantity > 0) {
                $issuedQty = (float) $movement->facility_issued_quantity;
                $breakdown['issued_breakdown'][] = [
                    'date' => $movement->created_at->format('Y-m-d H:i:s'),
                    'quantity' => $issuedQty,
                    'batch_number' => $movement->batch_number,
                    'movement_type' => $movement->movement_type,
                    'reference_type' => $movement->reference_type,
                    'reference_id' => $movement->reference_id
                ];

                $breakdown['daily_totals'][$date]['issued'] += $issuedQty;
                $totalIssuedCost += (float) ($movement->total_cost ?: 0);
            }

            // Batch breakdown
            if (!isset($breakdown['by_batch'][$batchKey])) {
                $breakdown['by_batch'][$batchKey] = [
                    'received' => 0,
                    'issued' => 0,
                    'expiry_date' => $movement->expiry_date
                ];
            }
            $breakdown['by_batch'][$batchKey]['received'] += (float) ($movement->facility_received_quantity ?: 0);
            $breakdown['by_batch'][$batchKey]['issued'] += (float) ($movement->facility_issued_quantity ?: 0);

            // Movement type breakdown
            if (!isset($breakdown['by_movement_type'][$movementType])) {
                $breakdown['by_movement_type'][$movementType] = [
                    'received' => 0,
                    'issued' => 0,
                    'transactions' => 0
                ];
            }
            $breakdown['by_movement_type'][$movementType]['received'] += (float) ($movement->facility_received_quantity ?: 0);
            $breakdown['by_movement_type'][$movementType]['issued'] += (float) ($movement->facility_issued_quantity ?: 0);
            $breakdown['by_movement_type'][$movementType]['transactions']++;

            // Reference type breakdown
            if (!isset($breakdown['by_reference_type'][$referenceType])) {
                $breakdown['by_reference_type'][$referenceType] = [
                    'received' => 0,
                    'issued' => 0,
                    'transactions' => 0
                ];
            }
            $breakdown['by_reference_type'][$referenceType]['received'] += (float) ($movement->facility_received_quantity ?: 0);
            $breakdown['by_reference_type'][$referenceType]['issued'] += (float) ($movement->facility_issued_quantity ?: 0);
            $breakdown['by_reference_type'][$referenceType]['transactions']++;

            $breakdown['daily_totals'][$date]['transactions']++;
        }

        // Cost analysis
        $breakdown['cost_analysis'] = [
            'total_received_cost' => $totalReceivedCost,
            'total_issued_cost' => $totalIssuedCost,
            'average_unit_cost' => $totalUnits > 0 ? $totalReceivedCost / $totalUnits : 0,
            'total_units_processed' => $totalUnits
        ];

        return $breakdown;
    }

    /**
     * Calculate opening balance from previous month's closing balance or current inventory
     */
    private function calculateOpeningBalance(Facility $facility, Product $product): float
    {
        // Get previous month
        $reportDate = Carbon::createFromFormat('Y-m', $this->reportPeriod);
        $prevMonth = $reportDate->copy()->subMonth()->format('Y-m');
        
        $previousReport = FacilityMonthlyReport::where([
            'facility_id' => $facility->id,
            'report_period' => $prevMonth,
        ])->first();

        if ($previousReport) {
            $previousReportItem = FacilityMonthlyReportItem::where([
                'parent_id' => $previousReport->id,
                'product_id' => $product->id,
            ])->first();

            return $previousReportItem ? (float) $previousReportItem->closing_balance : 0.0;
        }

        // If no previous report, get current inventory balance
        $inventory = FacilityInventory::where([
            'facility_id' => $facility->id,
            'product_id' => $product->id,
        ])->first();

        return $inventory && $inventory->quantity_available !== null ? (float) $inventory->quantity_available : 0.0;
    }

    /**
     * Calculate stockout days (simplified - can be enhanced with daily tracking)
     */
    private function calculateStockoutDays(Facility $facility, Product $product): int
    {
        // Simple calculation - check if current inventory is 0
        $inventory = FacilityInventory::where([
            'facility_id' => $facility->id,
            'product_id' => $product->id,
        ])->first();

        // If current inventory is 0 or doesn't exist, assume stockout for some days
        if (!$inventory || $inventory->quantity_available === null || $inventory->quantity_available <= 0) {
            return 5; // Assume 5 days of stockout as default
        }

        return 0;
    }

    /**
     * Handle a job failure.
     */
    public function failed(\Throwable $exception): void
    {
        Log::error("Monthly inventory report job failed permanently", [
            'facility_id' => $this->facilityId,
            'report_period' => $this->reportPeriod,
            'exception' => $exception->getMessage(),
            'attempts' => $this->attempts(),
            'memory_usage' => memory_get_usage(true)
        ]);
        
        // Clean up any partially created reports
        $this->cleanupPartialReports();
    }

    /**
     * Clean up any partially created reports on failure
     */
    private function cleanupPartialReports(): void
    {
        try {
            $facilities = $this->facilityId ? 
                collect([Facility::find($this->facilityId)])->filter() : 
                Facility::where('is_active', true)->get();

            foreach ($facilities as $facility) {
                // Find and delete incomplete reports (reports without items)
                $incompleteReports = FacilityMonthlyReport::where([
                    'facility_id' => $facility->id,
                    'report_period' => $this->reportPeriod
                ])->whereDoesntHave('items')->get();

                foreach ($incompleteReports as $report) {
                    Log::info("Cleaning up incomplete report {$report->id} for facility {$facility->id}");
                    $report->delete();
                }
            }
        } catch (\Exception $e) {
            Log::error("Error during cleanup: " . $e->getMessage());
        }
    }

    /**
     * Calculate the number of seconds to wait before retrying the job.
     */
    public function backoff(): array
    {
        return $this->backoff;
    }

    /**
     * Determine if the job should be retried based on the exception.
     */
    public function retryUntil(): \DateTime
    {
        // Don't retry after 4 hours total
        return now()->addHours(4);
    }
}
