<?php

namespace App\Services;

use App\Models\FacilityInventoryMovement;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Dispence;
use App\Models\DispenceItem;
use Carbon\Carbon;

class FacilityInventoryMovementService
{
    /**
     * Record facility received movement from transfer
     */
    public static function recordTransferReceived(Transfer $transfer, TransferItem $transferItem, $receivedQuantity)
    {
        // Only record if transfer is to a facility
        if (!$transfer->to_facility_id) {
            return null;
        }

        return FacilityInventoryMovement::recordFacilityReceived([
            'facility_id' => $transfer->to_facility_id,
            'product_id' => $transferItem->product_id,
            'source_type' => 'transfer',
            'source_id' => $transfer->id,
            'source_item_id' => $transferItem->id,
            'facility_received_quantity' => $receivedQuantity,
            'batch_number' => $transferItem->batch_number,
            'expiry_date' => $transferItem->expiry_date,
            'barcode' => $transferItem->barcode,
            'uom' => $transferItem->uom,
            'movement_date' => Carbon::now(),
            'reference_number' => $transfer->transferID,
            'notes' => "Facility received from transfer: {$transfer->transferID}",
        ]);
    }

    /**
     * Record facility issued movement when transfer is sent out
     */
    public static function recordTransferIssued(Transfer $transfer, $productId, $issuedQuantity, $batchNumber = null, $expiryDate = null, $barcode = null, $uom = null, $sourceItemId = null)
    {
        // Only record if transfer is from a facility
        if (!$transfer->from_facility_id) {
            return null;
        }

        return FacilityInventoryMovement::recordFacilityIssued([
            'facility_id' => $transfer->from_facility_id,
            'product_id' => $productId,
            'source_type' => 'transfer',
            'source_id' => $transfer->id,
            'source_item_id' => $sourceItemId,
            'facility_issued_quantity' => $issuedQuantity,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'barcode' => $barcode,
            'uom' => $uom,
            'movement_date' => Carbon::now(),
            'reference_number' => $transfer->transferID,
            'notes' => "Facility issued via transfer: {$transfer->transferID}",
        ]);
    }

    /**
     * Record facility received movement from order
     */
    public static function recordOrderReceived(Order $order, OrderItem $orderItem, $receivedQuantity, $batchNumber = null, $expiryDate = null, $barcode = null)
    {
        return FacilityInventoryMovement::recordFacilityReceived([
            'facility_id' => $order->facility_id,
            'product_id' => $orderItem->product_id,
            'source_type' => 'order',
            'source_id' => $order->id,
            'source_item_id' => $orderItem->id,
            'facility_received_quantity' => $receivedQuantity,
            'batch_number' => $batchNumber,
            'expiry_date' => $expiryDate,
            'barcode' => $barcode,
            'uom' => null, // Can be added if available in order items
            'movement_date' => Carbon::now(),
            'reference_number' => $order->order_number,
            'notes' => "Facility received from order: {$order->order_number}",
        ]);
    }

    /**
     * Record facility issued movement from dispense
     */
    public static function recordDispenseIssued(Dispence $dispence, DispenceItem $dispenceItem)
    {
        return FacilityInventoryMovement::recordFacilityIssued([
            'facility_id' => $dispence->facility_id,
            'product_id' => $dispenceItem->product_id,
            'source_type' => 'dispense',
            'source_id' => $dispence->id,
            'source_item_id' => $dispenceItem->id,
            'facility_issued_quantity' => $dispenceItem->quantity,
            'batch_number' => $dispenceItem->batch_number,
            'expiry_date' => $dispenceItem->expiry_date,
            'barcode' => $dispenceItem->barcode,
            'uom' => $dispenceItem->uom,
            'movement_date' => $dispence->dispence_date ? Carbon::parse($dispence->dispence_date) : Carbon::now(),
            'reference_number' => $dispence->dispence_number,
            'notes' => "Facility issued to patient: {$dispence->patient_name} - {$dispence->dispence_number}",
        ]);
    }

    /**
     * Get facility inventory balance for a product
     */
    public static function getFacilityProductBalance($facilityId, $productId, $batchNumber = null, $expiryDate = null)
    {
        $query = FacilityInventoryMovement::where('facility_id', $facilityId)
            ->where('product_id', $productId);

        if ($batchNumber) {
            $query->where('batch_number', $batchNumber);
        }

        if ($expiryDate) {
            $query->where('expiry_date', $expiryDate);
        }

        $movements = $query->get();
        
        $totalReceived = $movements->sum('facility_received_quantity');
        $totalIssued = $movements->sum('facility_issued_quantity');
        
        return [
            'total_facility_received' => $totalReceived,
            'total_facility_issued' => $totalIssued,
            'current_balance' => $totalReceived - $totalIssued,
            'movements_count' => $movements->count()
        ];
    }

    /**
     * Get facility inventory summary for a date range
     */
    public static function getFacilitySummary($facilityId, $startDate = null, $endDate = null)
    {
        $startDate = $startDate ?? Carbon::now()->subDays(30)->startOfDay();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $movements = FacilityInventoryMovement::byFacility($facilityId)
            ->byDateRange($startDate, $endDate)
            ->get();

        $received = $movements->where('movement_type', 'facility_received');
        $issued = $movements->where('movement_type', 'facility_issued');

        return [
            'period' => [
                'start_date' => $startDate->format('Y-m-d'),
                'end_date' => $endDate->format('Y-m-d')
            ],
            'facility_received' => [
                'total_quantity' => $received->sum('facility_received_quantity'),
                'count' => $received->count(),
                'by_source' => $received->groupBy('source_type')->map(function ($items, $source) {
                    return [
                        'source_type' => $source,
                        'quantity' => $items->sum('facility_received_quantity'),
                        'count' => $items->count()
                    ];
                })->values()
            ],
            'facility_issued' => [
                'total_quantity' => $issued->sum('facility_issued_quantity'),
                'count' => $issued->count(),
                'by_source' => $issued->groupBy('source_type')->map(function ($items, $source) {
                    return [
                        'source_type' => $source,
                        'quantity' => $items->sum('facility_issued_quantity'),
                        'count' => $items->count()
                    ];
                })->values()
            ],
            'net_movement' => $received->sum('facility_received_quantity') - $issued->sum('facility_issued_quantity')
        ];
    }

    /**
     * Get low stock alerts based on movement patterns
     */
    public static function getLowStockAlerts($facilityId, $daysToAnalyze = 30)
    {
        $startDate = Carbon::now()->subDays($daysToAnalyze)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Get all products with movements in the facility
        $productMovements = FacilityInventoryMovement::byFacility($facilityId)
            ->byDateRange($startDate, $endDate)
            ->with('product:id,name')
            ->get()
            ->groupBy('product_id');

        $alerts = [];

        foreach ($productMovements as $productId => $movements) {
            $totalReceived = $movements->sum('facility_received_quantity');
            $totalIssued = $movements->sum('facility_issued_quantity');
            $currentBalance = $totalReceived - $totalIssued;
            
            // Calculate average daily consumption
            $issuedMovements = $movements->where('movement_type', 'facility_issued');
            $avgDailyConsumption = $issuedMovements->sum('facility_issued_quantity') / $daysToAnalyze;
            
            // Alert if current balance is less than 7 days of consumption
            $daysOfStock = $avgDailyConsumption > 0 ? $currentBalance / $avgDailyConsumption : 999;
            
            if ($daysOfStock < 7 && $currentBalance > 0) {
                $alerts[] = [
                    'product_id' => $productId,
                    'product_name' => $movements->first()->product->name ?? 'Unknown',
                    'current_balance' => $currentBalance,
                    'avg_daily_consumption' => round($avgDailyConsumption, 2),
                    'days_of_stock' => round($daysOfStock, 1),
                    'alert_level' => $daysOfStock < 3 ? 'critical' : 'warning'
                ];
            }
        }

        return collect($alerts)->sortBy('days_of_stock')->values()->all();
    }

    /**
     * Record MOH dispense issued movement
     */
    public function recordMohDispenseIssued($mohDispense, $mohDispenseItem, $facilityId, $quantity, $batchNumber = null, $expiryDate = null)
    {
        try {
            return FacilityInventoryMovement::recordFacilityIssued([
                'facility_id' => $facilityId,
                'product_id' => $mohDispenseItem->product_id,
                'source_type' => 'moh_dispense',
                'source_id' => $mohDispense->id,
                'source_item_id' => $mohDispenseItem->id,
                'facility_issued_quantity' => $quantity,
                'batch_number' => $batchNumber,
                'expiry_date' => $expiryDate,
                'barcode' => $mohDispenseItem->batch_no ?? null,
                'uom' => null, // Can be added if available in MOH dispense items
                'movement_date' => $mohDispenseItem->dispense_date ?? Carbon::now(),
                'reference_number' => $mohDispense->moh_dispense_number,
                'notes' => "MOH Dispense issued: {$mohDispense->moh_dispense_number} - {$mohDispenseItem->source}",
            ]);
        } catch (\Exception $e) {
            \Log::error('Failed to record MOH dispense movement', [
                'moh_dispense_id' => $mohDispense->id,
                'moh_dispense_item_id' => $mohDispenseItem->id,
                'facility_id' => $facilityId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Bulk record movements (useful for data migration or bulk operations)
     */
    public static function bulkRecordMovements(array $movementsData)
    {
        $movements = [];
        
        foreach ($movementsData as $data) {
            $data['created_by'] = auth()->id();
            $data['created_at'] = Carbon::now();
            $data['updated_at'] = Carbon::now();
            $movements[] = $data;
        }

        return FacilityInventoryMovement::insert($movements);
    }
}
