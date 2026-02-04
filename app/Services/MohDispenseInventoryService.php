<?php

namespace App\Services;

use App\Models\MohDispense;
use App\Models\MohDispenseItem;
use App\Models\FacilityInventoryItem;
use App\Models\Product;
use App\Events\LowStockAlert;
use App\Notifications\InsufficientInventoryNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Carbon\Carbon;

class MohDispenseInventoryService
{
    /**
     * Process MohDispense and deduct from facility inventory
     */
    public function processMohDispense($mohDispenseId)
    {
        try {
            DB::beginTransaction();

            $mohDispense = MohDispense::with('items.product')->findOrFail($mohDispenseId);
            
            if ($mohDispense->status !== 'draft') {
                throw new \Exception('Only draft MOH dispenses can be processed.');
            }

            $facilityId = $mohDispense->facility_id;
            $processedItems = [];
            $insufficientItems = [];
            $totalProcessed = 0;

            // Process each MOH dispense item individually based on product + batch + expiry
            foreach ($mohDispense->items as $item) {
                $productId = $item->product_id;
                $product = $item->product;
                $batchNumber = $item->batch_no;
                $expiryDate = $item->expiry_date;
                $requiredQuantity = $item->quantity;

                // Get available inventory for this exact product + batch + expiry
                $availableInventory = FacilityInventoryItem::whereHas('inventory', function ($query) use ($facilityId) {
                        $query->where('facility_id', $facilityId);
                    })
                    ->where('product_id', $productId)
                    ->where('batch_number', $batchNumber)
                    ->whereDate('expiry_date', $expiryDate)
                    ->where('quantity', '>', 0)
                    ->sum('quantity');

                if ($availableInventory < $requiredQuantity) {
                    $insufficientItems[] = [
                        'product_id' => $productId,
                        'product_name' => $product->name,
                        'batch_number' => $batchNumber,
                        'expiry_date' => optional($expiryDate)->format('Y-m-d'),
                        'required_quantity' => $requiredQuantity,
                        'available_quantity' => $availableInventory,
                        'shortage' => $requiredQuantity - $availableInventory,
                    ];
                    continue; // Skip this item if insufficient inventory for its batch/expiry
                }

                // Deduct quantity from matching inventory rows (normally one, but support multiple)
                $remainingQuantity = $requiredQuantity;
                $inventories = FacilityInventoryItem::whereHas('inventory', function ($query) use ($facilityId) {
                        $query->where('facility_id', $facilityId);
                    })
                    ->where('product_id', $productId)
                    ->where('batch_number', $batchNumber)
                    ->whereDate('expiry_date', $expiryDate)
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->orderBy('created_at', 'asc')
                    ->get();

                foreach ($inventories as $inventory) {
                    if ($remainingQuantity <= 0) {
                        break;
                    }

                    $quantityToDeduct = min($remainingQuantity, $inventory->quantity);

                    // Deduct from inventory
                    $inventory->decrement('quantity', $quantityToDeduct);
                    $remainingQuantity -= $quantityToDeduct;
                    $totalProcessed += $quantityToDeduct;

                    // Record the deduction
                    $processedItems[] = [
                        'moh_dispense_item_id' => $item->id,
                        'inventory_item_id' => $inventory->id,
                        'quantity_deducted' => $quantityToDeduct,
                        'batch_number' => $inventory->batch_number,
                        'expiry_date' => $inventory->expiry_date,
                    ];
                }
            }

            // If there are insufficient items, trigger notifications and stop processing
            if (!empty($insufficientItems)) {
                $this->handleInsufficientInventory($mohDispense, $insufficientItems);
                
                DB::rollBack();
                
                return [
                    'success' => false,
                    'message' => 'Insufficient inventory for some items. Processing stopped.',
                    'insufficient_items' => $insufficientItems,
                    'processed_items' => $processedItems
                ];
            }

            // Update MOH dispense status to processed
            $mohDispense->update([
                'status' => 'processed',
            ]);

            // Record facility inventory movements
            $this->recordInventoryMovements($mohDispense, $processedItems);

            DB::commit();

            Log::info('MOH Dispense processed successfully', [
                'moh_dispense_id' => $mohDispenseId,
                'facility_id' => $facilityId,
                'total_processed' => $totalProcessed,
                'items_processed' => count($processedItems)
            ]);

            return [
                'success' => true,
                'message' => 'MOH dispense processed successfully.',
                'data' => [
                    'moh_dispense_id' => $mohDispenseId,
                    'moh_dispense_number' => $mohDispense->moh_dispense_number,
                    'total_processed' => $totalProcessed,
                    'items_processed' => count($processedItems),
                    'processed_items' => $processedItems
                ]
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            
            Log::error('MOH Dispense processing failed', [
                'moh_dispense_id' => $mohDispenseId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return [
                'success' => false,
                'message' => 'Error processing MOH dispense: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get available inventory quantity for a product
     */
    private function getAvailableInventory($facilityId, $productId)
    {
        return FacilityInventoryItem::whereHas('inventory', function($query) use ($facilityId) {
            $query->where('facility_id', $facilityId);
        })
        ->where('product_id', $productId)
        ->where('quantity', '>', 0)
        ->sum('quantity');
    }

    /**
     * Get inventories for deduction (FIFO - First In, First Out)
     */
    private function getInventoriesForDeduction($facilityId, $productId, $requiredQuantity)
    {
        return FacilityInventoryItem::whereHas('inventory', function($query) use ($facilityId) {
            $query->where('facility_id', $facilityId);
        })
        ->where('product_id', $productId)
        ->where('quantity', '>', 0)
        ->orderBy('expiry_date', 'asc') // FIFO by expiry date
        ->orderBy('created_at', 'asc')  // Then by creation date
        ->get();
    }

    /**
     * Handle insufficient inventory scenario
     */
    private function handleInsufficientInventory($mohDispense, $insufficientItems)
    {
        // Update MOH dispense status to indicate inventory issues
        $mohDispense->update([
            'status' => 'insufficient_inventory',
        ]);

        // Trigger low stock alert event
        foreach ($insufficientItems as $item) {
            event(new LowStockAlert([
                'facility_id' => $mohDispense->facility_id,
                'product_id' => $item['product_id'],
                'product_name' => $item['product_name'],
                'required_quantity' => $item['required_quantity'],
                'available_quantity' => $item['available_quantity'],
                'shortage' => $item['shortage'],
                'source' => 'moh_dispense',
                'moh_dispense_id' => $mohDispense->id,
                'moh_dispense_number' => $mohDispense->moh_dispense_number,
            ]));
        }

        // Send notification to facility users
        $this->sendInsufficientInventoryNotification($mohDispense, $insufficientItems);

        Log::warning('MOH Dispense processing stopped due to insufficient inventory', [
            'moh_dispense_id' => $mohDispense->id,
            'facility_id' => $mohDispense->facility_id,
            'insufficient_items' => $insufficientItems
        ]);
    }

    /**
     * Send notification about insufficient inventory
     */
    private function sendInsufficientInventoryNotification($mohDispense, $insufficientItems)
    {
        try {
            // Get facility users who should be notified
            $facilityUsers = \App\Models\User::where('facility_id', $mohDispense->facility_id)
                ->where('role', '!=', 'patient')
                ->get();

            if ($facilityUsers->isNotEmpty()) {
                Notification::send($facilityUsers, new InsufficientInventoryNotification([
                    'moh_dispense_number' => $mohDispense->moh_dispense_number,
                    'facility_name' => $mohDispense->facility->name ?? 'Unknown Facility',
                    'insufficient_items' => $insufficientItems,
                    'total_shortage' => array_sum(array_column($insufficientItems, 'shortage')),
                ]));
            }
        } catch (\Exception $e) {
            Log::error('Failed to send insufficient inventory notification', [
                'moh_dispense_id' => $mohDispense->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Record facility inventory movements
     */
    private function recordInventoryMovements($mohDispense, $processedItems)
    {
        try {
            $facilityInventoryMovementService = new FacilityInventoryMovementService();

            foreach ($processedItems as $item) {
                $mohDispenseItem = MohDispenseItem::find($item['moh_dispense_item_id']);
                
                if ($mohDispenseItem) {
                    $facilityInventoryMovementService->recordMohDispenseIssued(
                        $mohDispense,
                        $mohDispenseItem,
                        $mohDispense->facility_id,
                        $item['quantity_deducted'],
                        $item['batch_number'],
                        $item['expiry_date']
                    );
                }
            }
        } catch (\Exception $e) {
            Log::error('Failed to record inventory movements for MOH dispense', [
                'moh_dispense_id' => $mohDispense->id,
                'error' => $e->getMessage()
            ]);
        }
    }

    /**
     * Validate inventory before processing
     */
    public function validateInventory($mohDispenseId)
    {
        try {
            $mohDispense = MohDispense::with('items.product')->findOrFail($mohDispenseId);
            $facilityId = $mohDispense->facility_id;
            $validationResults = [];
            $hasInsufficientItems = false;

            \Log::info('MOH Dispense validation started', [
                'moh_dispense_id' => $mohDispenseId,
                'facility_id' => $facilityId,
                'items_count' => $mohDispense->items->count()
            ]);

            $itemsByProduct = $mohDispense->items->groupBy('product_id');

        foreach ($itemsByProduct as $productId => $items) {
            $totalRequiredQuantity = $items->sum('quantity');
            $availableInventory = $this->getAvailableInventory($facilityId, $productId);
            $product = $items->first()->product;

            $validationResults[] = [
                'product_id' => $productId,
                'product_name' => $product->name,
                'required_quantity' => $totalRequiredQuantity,
                'available_quantity' => $availableInventory,
                'sufficient' => $availableInventory >= $totalRequiredQuantity,
                'shortage' => max(0, $totalRequiredQuantity - $availableInventory)
            ];

            if ($availableInventory < $totalRequiredQuantity) {
                $hasInsufficientItems = true;
            }
        }

            \Log::info('MOH Dispense validation completed', [
                'moh_dispense_id' => $mohDispenseId,
                'can_process' => !$hasInsufficientItems,
                'validation_results_count' => count($validationResults)
            ]);

            return [
                'can_process' => !$hasInsufficientItems,
                'validation_results' => $validationResults,
                'has_insufficient_items' => $hasInsufficientItems
            ];
            
        } catch (\Exception $e) {
            \Log::error('MOH Dispense validation service error', [
                'moh_dispense_id' => $mohDispenseId,
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            throw $e;
        }
    }
}
