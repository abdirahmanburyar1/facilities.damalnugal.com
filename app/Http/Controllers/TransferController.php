<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia;
use App\Models\Inventory;
use App\Models\Warehouse;
use App\Models\Facility;
use App\Models\FacilityInventory;
use App\Models\FacilityInventoryItem;
use App\Models\Reason;
use App\Models\Transfer;
use App\Models\TransferItem;
use App\Models\Product;
use App\Models\IssuedQuantity;
use App\Models\Disposal;
use App\Models\BackOrderHistory;
use App\Models\BackOrder;
use App\Models\InventoryItem;
use App\Models\PackingListDifference;
use App\Models\InventoryAllocation;
use App\Models\Liquidate;
use App\Models\ReceivedQuantity;
use App\Models\Driver;
use App\Models\LogisticCompany;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Notification;
use App\Notifications\TransferCreated;
use App\events\TransferStatusChanged;
use App\events\InventoryUpdated;
use App\events\FacilityInventoryUpdated;
use App\events\FacilityInventoryTestEvent;
use Illuminate\Support\Facades\Log;
use App\Http\Resources\TransferResource;

use App\Services\FacilityInventoryMovementService;

class TransferController extends Controller
{
    
    public function receivedQuantity(Request $request){
        try {
            return DB::transaction(function () use ($request) {
                $request->validate([
                    'allocation_id' => 'required|exists:inventory_allocations,id',
                    'received_quantity' => 'required|numeric|min:0',
                ]);
                
                $allocation = InventoryAllocation::find($request->allocation_id);
                
                // Use updated_quantity if it's set (not null and greater than 0), otherwise use allocated_quantity
                $effectiveQuantity = ($allocation->updated_quantity !== null && $allocation->updated_quantity > 0) ? $allocation->updated_quantity : $allocation->allocated_quantity;
                
                // Validate received quantity doesn't exceed effective quantity
                if ($request->received_quantity > $effectiveQuantity) {
                    return response()->json("Received quantity cannot exceed effective quantity (updated_quantity if set and greater than 0, else allocated_quantity)", 400);
                }
                
                $allocation->received_quantity = $request->received_quantity;
                $allocation->save();
                PackingListDifference::where('inventory_allocation_id', $request->allocation_id)->delete();
    
                return response()->json("Success", 200);
                
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function receivedAllocationQuantity(Request $request){
        try {
            $request->validate([
                'allocation_id' => 'required|exists:inventory_allocations,id',
                'received_quantity' => 'required|numeric|min:0',
            ]);

            $allocation = InventoryAllocation::find($request->allocation_id);
            $transferItem = $allocation->transfer_item;
            $transfer = $transferItem->transfer;

            // Validate that user belongs to the receiving facility/warehouse
            $currentUser = auth()->user();
            $isReceiver = ($transfer->to_warehouse_id === $currentUser->warehouse_id) || 
                         ($transfer->to_facility_id === $currentUser->facility_id);

            if (!$isReceiver) {
                return response()->json("You are not authorized to receive this transfer", 403);
            }

            // Use update_quantity if it's set (not zero), otherwise use allocated_quantity
            $effectiveQuantity = ($allocation->update_quantity ?? 0) !== 0 ? $allocation->update_quantity : $allocation->allocated_quantity;
            
            // Validate received quantity doesn't exceed allocated quantity
            if ($request->received_quantity > $effectiveQuantity) {
                return response()->json("Received quantity cannot exceed allocated quantity", 400);
            }

            // Calculate total PackingListDifference quantities for this allocation
            $totalDifferences = $allocation->differences()->sum('quantity');
            
            // Validate that: allocated_quantity - received_quantity = total_differences
            $expectedReceivedQuantity = $effectiveQuantity - $totalDifferences;
            
            if ($request->received_quantity != $expectedReceivedQuantity) {
                return response()->json([
                    "message" => "Received quantity must equal allocated quantity minus differences",
                    "allocated_quantity" => $effectiveQuantity,
                    "total_differences" => $totalDifferences,
                    "expected_received_quantity" => $expectedReceivedQuantity,
                    "provided_received_quantity" => $request->received_quantity
                ], 400);
            }

            // Update allocation received quantity
            $allocation->received_quantity = $request->received_quantity;
            $allocation->save();

            // Update transfer item received quantity (sum of all allocations)
            $totalReceived = $transferItem->inventory_allocations->sum('received_quantity');
            $transferItem->received_quantity = $totalReceived;
            $transferItem->save();

            // Dispatch inventory updated // event
            // event(new InventoryUpdated($transfer->from_facility_id));

            return response()->json('Allocation received quantity updated successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    // dispatch info method

    public function changeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'status' => 'required|in:reviewed,approved,rejected,in_process,dispatched,delivered,received'
            ]);

            $transfer = Transfer::with('items.inventory_allocations.backorders')->find($request->transfer_id);
            if(!$transfer){
                return response()->json("Not Found or you are not authorized to take this action", 500);
            }
            
            // Determine user's role in the transfer
            $currentUser = auth()->user();
            $currentWarehouse = $currentUser->warehouse;
            $currentFacility = $currentUser->facility_id;
            
            // User is sender if their warehouse/facility is the source
            $isSender = ($transfer->from_warehouse_id === $currentWarehouse?->id) || 
                       ($transfer->from_facility_id === $currentFacility);
            
            // User is receiver if their warehouse/facility is the destination
            $isReceiver = ($transfer->to_warehouse_id === $currentWarehouse?->id) || 
                         ($transfer->to_facility_id === $currentFacility);
            
            // Store the old status before making any changes
            $oldStatus = $transfer->status;
            $newStatus = $request->status;

            // pending -> reviewed (SENDER ACTION)
            if($oldStatus == 'pending' && $newStatus == 'reviewed' && $isSender && auth()->user()->can('transfer.review')){                
                $transfer->update([
                    'status' => 'reviewed',
                    'reviewed_by' => auth()->id(),
                    'reviewed_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // pending -> rejected (branch) (SENDER ACTION)
            if($oldStatus == 'pending' && $newStatus == 'rejected' && $isSender && auth()->user()->can('transfer.approve')){
                $transfer->update([
                    'status' => 'rejected',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // reviewed -> approved (SENDER ACTION)
            if($oldStatus == 'reviewed' && $newStatus == 'approved' && $isSender && auth()->user()->can('transfer.approve')){                
                $transfer->update([
                    'status' => 'approved',
                    'approved_by' => auth()->id(),
                    'approved_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // reviewed -> rejected (branch) (SENDER ACTION)
            if($oldStatus == 'reviewed' && $newStatus == 'rejected' && $isSender && auth()->user()->can('transfer.approve')){
                $transfer->update([
                    'status' => 'rejected',
                    'rejected_by' => auth()->id(),
                    'rejected_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // approved -> in_process (SENDER ACTION)
            if($oldStatus == 'approved' && $newStatus == 'in_process' && $isSender && auth()->user()->can('transfer.in_process')){
                $transfer->update([
                    'status' => 'in_process',
                    'processed_by' => auth()->id(),
                    'processed_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }

            // in_process -> dispatched (SENDER ACTION)
            if($oldStatus == 'in_process' && $newStatus == 'dispatched' && $isSender && auth()->user()->can('transfer.dispatch')){
                $transfer->update([
                    'status' => 'dispatched',
                    'dispatched_by' => auth()->id(),    
                    'dispatched_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // dispatched -> delivered (RECEIVER ACTION)
            if($oldStatus == 'dispatched' && $newStatus == 'delivered' && $isReceiver && auth()->user()->can('transfer.deliver')){
                $transfer->update([
                    'status' => 'delivered',
                    'delivered_by' => auth()->id(),    
                    'delivered_at' => now()
                ]);
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            // delivered -> received (RECEIVER ACTION)
            if($oldStatus == 'delivered' && $newStatus == 'received' && $isReceiver && auth()->user()->can('transfer.receive')){
                $user = auth()->user();
            
                foreach ($transfer->items as $item) {
                    // Debug information for this item
                    
                    foreach ($item->inventory_allocations as $allocation) {
                        // Calculate total back order quantity for this allocation
                        // Use update_quantity if it's set (not zero), otherwise use allocated_quantity
                        $effectiveQuantity = ($allocation->update_quantity ?? 0) !== 0 ? $allocation->update_quantity : $allocation->allocated_quantity;
                
                if((int) $effectiveQuantity < (int) $allocation->backorders->sum('quantity')){
                    DB::rollback();
                    return response()->json('Backorder quantities exceeded the allocated quantity', 500);
                }
                $finalQuantity = $effectiveQuantity - $allocation->backorders->sum('quantity');
                        
                        $inventory = FacilityInventory::where('facility_id', $transfer->to_facility_id)
                            ->where('product_id', $allocation->product_id)
                            ->first();
    
                        if($inventory){
                            $inventoryItem = $inventory->items()->where('batch_number', $allocation->batch_number)->first();
                            if($inventoryItem){
                                $inventoryItem->increment('quantity', $finalQuantity);
                            }else{
                                $inventory->items()->create([
                                    'product_id' => $allocation->product_id,
                                    'quantity' => $finalQuantity,
                                    'expiry_date' => $allocation->expiry_date,
                                    'batch_number' => $allocation->batch_number,
                                    'barcode' => $allocation->barcode,
                                    'uom' => $allocation->uom,
                                    'unit_cost' => $allocation->unit_cost,
                                    'total_cost' => $allocation->unit_cost * $finalQuantity
                                ]);
                            }
                            
                        }else{
                            $inventory = FacilityInventory::create([
                                'facility_id' => $transfer->to_facility_id,
                                'product_id' => $allocation->product_id
                            ]);
                            $inventory->items()->create([
                                'product_id' => $allocation->product_id,
                                'batch_number' => $allocation->batch_number,
                                'expiry_date' => $allocation->expiry_date,
                                'quantity' => $finalQuantity,
                                'barcode' => $allocation->barcode,
                                'uom' => $allocation->uom,
                                'unit_cost' => $allocation->unit_cost,
                                'total_cost' => $allocation->unit_cost * $finalQuantity
                            ]);
                        }
                        
                        // Record facility inventory movement for received quantity
                        // if ($finalQuantity > 0) {
                        //     FacilityInventoryMovementService::recordTransferReceived(
                        //         $transfer,
                        //         $item,
                        //         $finalQuantity,
                        //         $allocation->batch_number,
                        //         $allocation->expiry_date,
                        //         $allocation->barcode
                        //     );
                        // }
                    }
                }
                
                // Update transfer status to received
                $transfer->status = 'received';
                $transfer->received_at = Carbon::now();
                $transfer->received_by = auth()->user()->id;
                $transfer->save();
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
            }
            
            DB::commit();
            
            // Return debug information along with success message
            return response()->json("Transfer status changed successfully", 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function index(Request $request)
    {
        // Start building the query
        $query = Transfer::query();

        $user = auth()->user();
        $currentFacilityId = $user?->facility_id;
        $currentWarehouseId = $user?->warehouse_id;

        // Only show transfers relevant to the current user's facility/warehouse
        $query->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
            if ($currentFacilityId) {
                $q->orWhere('to_facility_id', $currentFacilityId)
                  ->orWhere('from_facility_id', $currentFacilityId);
            }

            if ($currentWarehouseId) {
                $q->orWhere('to_warehouse_id', $currentWarehouseId)
                  ->orWhere('from_warehouse_id', $currentWarehouseId);
            }
        })
        ->with('fromWarehouse', 'toWarehouse', 'fromFacility', 'toFacility', 'items')
        ->withCount('items')
        ->latest('created_at');
        
        // Apply filters
        // Filter by transfer direction (top level tab)
        if ($request->filled('direction') && $request->direction !== 'all') {
            if ($request->direction === 'in') {
                // In Transfers: where user's facility/warehouse is the destination
                $query->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
                    if ($currentFacilityId) {
                        $q->orWhere('to_facility_id', $currentFacilityId);
                    }
                    if ($currentWarehouseId) {
                        $q->orWhere('to_warehouse_id', $currentWarehouseId);
                    }
                });
            } elseif ($request->direction === 'out') {
                // Out Transfers: where user's facility/warehouse is the source
                $query->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
                    if ($currentFacilityId) {
                        $q->orWhere('from_facility_id', $currentFacilityId);
                    }
                    if ($currentWarehouseId) {
                        $q->orWhere('from_warehouse_id', $currentWarehouseId);
                    }
                });
            }
        }
        
        // Filter by status tab
        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }
        
        // Filter by search term
        if ($request->has('search') && !empty($request->search)) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('transferID', 'like', $searchTerm)
                  ->orWhereHas('fromFacility', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('toFacility', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('fromWarehouse', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  })
                  ->orWhereHas('toWarehouse', function($q) use ($searchTerm) {
                      $q->where('name', 'like', $searchTerm);
                  });
            });
        }
        
        if ($request->filled('transfer_type')) {
            switch ($request->transfer_type) {
                case 'Warehouse to Warehouse':
                    $query->whereNotNull('from_warehouse_id')
                          ->whereNotNull('to_warehouse_id');
                    break;
        
                case 'Facility to Facility':
                    $query->whereNotNull('from_facility_id')
                          ->whereNotNull('to_facility_id');
                    break;
        
                case 'Facility to Warehouse':
                    $query->whereNotNull('from_facility_id')
                          ->whereNotNull('to_warehouse_id');
                    break;
        
                case 'Warehouse to Facility':
                    $query->whereNotNull('from_warehouse_id')
                          ->whereNotNull('to_facility_id');
                    break;
            }
        }
        

        if($request->filled('date_from') && !$request->filled('date_to')){
            $query->whereDate('transfer_date', $request->date_from);
        }

        if($request->filled('transfer_type') && $request->transfer_type == 'Facility'){
            $query->whereHas('toFacility')
            ->whereHas('fromFacility');
        }

        if($request->filled('transfer_type') && $request->transfer_type == 'Warehouse'){
            $query->whereHas('toWarehouse')
            ->whereHas('fromWarehouse');
        }
        
        // Filter by date range
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('transfer_date', [$request->date_from, $request->date_to]);
        }
        
        // Execute the query
        $transfers = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
        ->withQueryString();
        $transfers->setPath(url()->current()); // Force Laravel to use full URLs
        
        // Get filtered transfers for statistics
        $filteredQuery = Transfer::query();
        $filteredQuery->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
            if ($currentFacilityId) {
                $q->orWhere('to_facility_id', $currentFacilityId)
                  ->orWhere('from_facility_id', $currentFacilityId);
            }
            if ($currentWarehouseId) {
                $q->orWhere('to_warehouse_id', $currentWarehouseId)
                  ->orWhere('from_warehouse_id', $currentWarehouseId);
            }
        });
            
        // Apply same direction filter for statistics
        if ($request->filled('direction') && $request->direction !== 'all') {
            if ($request->direction === 'in') {
                $filteredQuery->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
                    if ($currentFacilityId) {
                        $q->orWhere('to_facility_id', $currentFacilityId);
                    }
                    if ($currentWarehouseId) {
                        $q->orWhere('to_warehouse_id', $currentWarehouseId);
                    }
                });
            } elseif ($request->direction === 'out') {
                $filteredQuery->where(function ($q) use ($currentFacilityId, $currentWarehouseId) {
                    if ($currentFacilityId) {
                        $q->orWhere('from_facility_id', $currentFacilityId);
                    }
                    if ($currentWarehouseId) {
                        $q->orWhere('from_warehouse_id', $currentWarehouseId);
                    }
                });
            }
        }
        
        $allTransfers = $filteredQuery->get();
        $total = $allTransfers->count();
        $approvedCount = $allTransfers->whereIn('status', ['approved'])->count();
        $reviewedCount = $allTransfers->whereIn('status', ['reviewed'])->count();
        $inProcessCount = $allTransfers->whereIn('status', ['in_process'])->count();
        $dispatchedCount = $allTransfers->where('status', 'dispatched')->count();
        $deliveredCount = $allTransfers->where('status', 'delivered')->count();
        $receivedCount = $allTransfers->where('status', 'received')->count();
        $rejectedCount = $allTransfers->where('status', 'rejected')->count();
        $pendingCount = $allTransfers->where('status', 'pending')->count();
        
        $statistics = [
            'approved' => [
                'count' => $approvedCount,
                'percentage' => $total > 0 ? round(($approvedCount / $total) * 100) : 0,
                'stages' => ['approved']
            ],
            'pending' => [
                'count' => $pendingCount,
                'percentage' => $total > 0 ? round(($pendingCount / $total) * 100) : 0,
                'stages' => ['pending']
            ],
            'reviewed' => [
                'count' => $reviewedCount,
                'percentage' => $total > 0 ? round(($reviewedCount / $total) * 100) : 0,
                'stages' => ['reviewed']
            ],
            'in_process' => [
                'count' => $inProcessCount,
                'percentage' => $total > 0 ? round(($inProcessCount / $total) * 100) : 0,
                'stages' => ['in_process']
            ],
            'dispatched' => [
                'count' => $dispatchedCount,
                'percentage' => $total > 0 ? round(($dispatchedCount / $total) * 100) : 0,
                'stages' => ['dispatched']
            ],
            'delivered' => [
                'count' => $deliveredCount,
                'percentage' => $total > 0 ? round(($deliveredCount / $total) * 100) : 0,
                'stages' => ['delivered']
            ],
            'received' => [
                'count' => $receivedCount,
                'percentage' => $total > 0 ? round(($receivedCount / $total) * 100) : 0,
                'stages' => ['received']
            ],
            'rejected' => [
                'count' => $rejectedCount,
                'percentage' => $total > 0 ? round(($rejectedCount / $total) * 100) : 0,
                'stages' => ['rejected']
            ]
        ];
        
        // Get data for filter dropdowns
        $facilities = Facility::pluck('name')->toArray();
        $warehouses = Warehouse::pluck('name')->toArray();
        $locations = DB::table('locations')->select('id', 'location')->orderBy('location')->get();

        return inertia('Transfer/Index', [
            'transfers' => TransferResource::collection($transfers),
            'statistics' => $statistics,
            'facilities' => $facilities,
            'warehouses' => $warehouses,
            'locations' => $locations,
            'filters' => $request->only([
                'search',
                'transfer_type',
                'facility',
                'warehouse',
                'from_warehouse',
                'to_warehouse',
                'from_facility',
                'to_facility',
                'date_from',
                'date_to',
                'status',
                'direction',
                'per_page',
                'page'
            ])
        ]);
    }

    public function store(Request $request)
    {
        DB::beginTransaction();
        try {
            $request->validate([
                'source_type' => 'required|in:warehouse,facility',
                'source_id' => 'required|integer',
                'destination_type' => 'required|in:warehouse,facility',
                'destination_id' => 'required|integer',
                'transfer_date' => 'required|date',
                'transferID' => 'required',
                'items' => 'required|array',
                // 'items.*.product_id' => 'required|integer',
                // 'items.*.quantity' => 'required|integer|min:1',
                // 'items.*.details' => 'required|array',
                // 'items.*.details.*.quantity_to_transfer' => 'required|integer|min:1',
                // 'items.*.details.*.id' => 'required|integer',
                // 'items.*.details.*.transfer_reason' => 'required|string',
                // 'notes' => 'nullable|string',
                'transfer_type' => 'nullable|string'
            ]);
    
            // Determine transfer type based on source and destination types
            $sourceTypeFormatted = ucfirst($request->source_type);
            $destinationTypeFormatted = ucfirst($request->destination_type);
            $automaticTransferType = "{$sourceTypeFormatted} to {$destinationTypeFormatted}";

            $transferData = [
                'transferID' => $request->transferID,
                'transfer_date' => $request->transfer_date,
                'from_warehouse_id' => $request->source_type === 'warehouse' ? $request->source_id : null,
                'from_facility_id' => $request->source_type === 'facility' ? $request->source_id : null,
                'to_warehouse_id' => $request->destination_type === 'warehouse' ? $request->destination_id : null,
                'to_facility_id' => $request->destination_type === 'facility' ? $request->destination_id : null,
                'transfer_type' => $automaticTransferType,
                'created_by' => auth()->id(),
            ];
    
            $transfer = Transfer::create($transferData);
    
            foreach ($request->items as $item) {
                // Create transfer item for this product
                $transferItem = $transfer->items()->create([
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'], // Total requested quantity
                    'quantity_to_release' => $item['quantity'],
                    'quantity_per_unit' => $item['quantity'] // Total quantity for this item
                ]);

                // Process each detail item with specific quantities to transfer
                foreach ($item['details'] as $detail) {
                    $quantityToTransfer = $detail['quantity_to_transfer'];
                    
                    if ($quantityToTransfer <= 0) continue;

                    // Find the specific inventory item by ID
                    if ($request->source_type === 'warehouse') {
                        $inventoryItem = InventoryItem::find($detail['id']);
                    } else {
                        $inventoryItem = FacilityInventoryItem::find($detail['id']);
                    }

                    if (!$inventoryItem) {
                        throw new \Exception("Inventory item with ID {$detail['id']} not found");
                    }

                    // Verify we have enough quantity
                    if ($quantityToTransfer > $inventoryItem->quantity) {
                        throw new \Exception("Insufficient quantity. Available: {$inventoryItem->quantity}, Requested: {$quantityToTransfer}");
                    }
                    
                    // Create inventory allocation record for detailed tracking
                    $transferItem->inventory_allocations()->create([
                        'product_id' => $item['product_id'],
                        'warehouse_id' => $request->source_type === 'warehouse' ? $request->source_id : null,
                        'location' => $inventoryItem->location,
                        'batch_number' => $inventoryItem->batch_number,
                        'expiry_date' => $inventoryItem->expiry_date,
                        'allocated_quantity' => $quantityToTransfer,
                        'uom' => $inventoryItem->uom,
                        'barcode' => $inventoryItem->barcode,
                        'allocation_type' => 'transfer',
                        'unit_cost' => $inventoryItem->unit_cost ?? 0,
                        'total_cost' => $quantityToTransfer * ($inventoryItem->unit_cost ?? 0),
                        'transfer_reason' => $detail['transfer_reason'],
                    ]);

                    // Deduct from source inventory
                    $inventoryItem->quantity -= $quantityToTransfer;
                    $inventoryItem->save();
                }
        
            }
    
            $transfer->load(['fromWarehouse', 'toWarehouse', 'fromFacility', 'toFacility', 'items.product']);
    
            if ($transfer->to_warehouse_id && $transfer->toWarehouse?->manager_email) {
                Notification::route('mail', $transfer->toWarehouse->manager_email)
                    ->notify(new TransferCreated($transfer));
            } elseif ($transfer->to_facility_id && $transfer->toFacility?->email) {
                Notification::route('mail', $transfer->toFacility->email)
                    ->notify(new TransferCreated($transfer));
            }
    
            DB::commit();
            return response()->json('Transfer created successfully', 200);
    
        } catch (\Throwable $e) {
            DB::rollBack();
            logger()->error($e);
            return response()->json('Failed to create transfer: ' . $e->getMessage(), 500);
        }
    }
    
    public function dispatchInfo(Request $request){
        try {
            return DB::transaction(function() use ($request){
                $request->validate([
                    'dispatch_date' => 'required|date',
                    'driver_id' => 'required|exists:drivers,id',
                    'driver_number' => 'required|string',
                    'plate_number' => 'required|string',
                    'no_of_cartoons' => 'required|numeric',
                    'transfer_id' => 'required|exists:transfers,id',
                    'logistic_company_id' => 'required|exists:logistic_companies,id',
                    'status' => 'required|string'
                ]);

                $transfer = Transfer::with('dispatch')->find($request->transfer_id);
                $transfer->dispatch()->create([
                    'transfer_id' => $request->transfer_id,
                    'dispatch_date' => $request->dispatch_date,
                    'driver_id' => $request->driver_id,
                    'logistic_company_id' => $request->logistic_company_id,
                    'driver_number' => $request->driver_number,
                    'plate_number' => $request->plate_number,
                    'no_of_cartoons' => $request->no_of_cartoons,
                ]);

                $transfer->status = $request->status;
                $transfer->dispatched_at = now();
                $transfer->dispatched_by = auth()->user()->id;
                $transfer->save();
                
                return response()->json("Dispatched Successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function show($id){
        try {
            DB::beginTransaction();

            $transfer = Transfer::where('id', $id)
            ->with([
                'items.product.category',
                'dispatch.driver',
                'dispatch.logistic_company',
                'items.inventory_allocations.location',
                'items.inventory_allocations.warehouse',
                'items.inventory_allocations.differences',
                'items.differences', 
                'backorders', 
                'toFacility', 
                'fromFacility',
                'toWarehouse',
                'fromWarehouse',
                'user',
                'reviewedBy', 
                'approvedBy', 
                'processedBy',
                'dispatchedBy',
                'deliveredBy',
                'receivedBy'
            ])
            ->first();
            
            // Get drivers with their companies
            $drivers = Driver::with('company')->where('is_active', true)->get();
                
            // Get all companies for the driver form
            $companyOptions = LogisticCompany::where('is_active', true)
                ->get()
                ->map(function($company) {
                    return [
                        'id' => $company->id,
                        'name' => $company->name,
                        'isAddNew' => false
                    ];
                });
            
            logger()->info($transfer);
            
            DB::commit();
            return inertia('Transfer/Show', [
                'transfer' => $transfer,
                'drivers' => $drivers,
                'companyOptions' => $companyOptions
            ]);
        } catch (\Throwable $th) {
            DB::rollBack();
            logger()->error('Transfer show error: ' . $th->getMessage());
            logger()->error('Stack trace: ' . $th->getTraceAsString());
            return redirect()->back()->with('error', 'An error occurred while loading the transfer');    
        }
    }
    
    public function create(Request $request){
        $warehouses = Warehouse::select('id','name')->get();
        $facilities = Facility::select('id','name')->get();
        $transferID = Transfer::generateTransferId();
        $inventories = Product::whereHas('inventory', function($query) {
            $query->where('facility_id', auth()->user()->facility_id);
        })
        ->select('id','name')
        ->get();
        $facilityID = auth()->user()->facility_id;
        
        return inertia('Transfer/Create', [
            'warehouses' => $warehouses,
            'facilities' => $facilities,
            'transferID' => $transferID,
            'inventories' => $inventories,
            'facilityID' => $facilityID,
            'reasons' => Reason::pluck('name')->toArray()
        ]);
    }
    
    /**
     * Delete a transfer item
     */
    public function destroyItem($id)
    {
        try {
            $transferItem = TransferItem::findOrFail($id);
            
            // Check if the transfer is in a state where items can be deleted
            $transfer = Transfer::findOrFail($transferItem->transfer_id);
            
            if (!in_array($transfer->status, ['pending', 'draft'])) {
                return response()->json('Cannot delete items from a transfer that is not in pending or draft status', 500);
            }
            
            // Delete the transfer item
            $transferItem->delete();
            
            return response()->json('Transfer item deleted successfully');
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    // get transfer source imventory
    public function getSourceInventoryDetail(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
        ]);
        
        try {
            return DB::transaction(function() use ($request){
                // Get current date for expiry comparison
                $currentDate = Carbon::now()->toDateString();
                
                $inventory = FacilityInventoryItem::where('product_id', $request->product_id)
                    ->whereHas('inventory', function($query) {
                        $query->where('facility_id', auth()->user()->facility_id);
                    })
                    ->where('quantity', '>', 0)
                    ->where(function($query) use ($currentDate) {
                        $query->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', $currentDate);
                    })
                    ->with('product:id,name')
                    ->get();
                
                // Check if no valid inventory items are available
                if ($inventory->isEmpty()) {
                    return response()->json('No available inventory items for transfer', 500);
                }
                
                return response()->json($inventory, 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function updateItem(Request $request){
        try {
            DB::beginTransaction();
            
            $request->validate([
                'id' => 'required|exists:transfer_items,id',
                'quantity' => 'required|numeric|min:1',
            ]);
            
            $transferItem = TransferItem::with('transfer')->findOrFail($request->id);
            $transfer = $transferItem->transfer;

            if($transferItem->quantity <= 0) {
                $transferItem->quantity = $request->quantity;
                $transferItem->save();
                $transferItem->refresh();
            }

            if (!in_array($transfer->status, ['pending'])) {
                return response()->json('Cannot update quantity for transfers that are not in pending status', 500);
            }

            // Use the requested quantity directly for transfers
            $newQuantityToRelease = (int) ceil($request->quantity);
            $oldQuantityToRelease = $transferItem->quantity_to_release ?? 0;

            // Always use from_facility_id as the source for FacilityInventory
            $isFromWarehouse = false;
            $sourceId = $transfer->from_facility_id;

            // Case 1: Decrease
            if ($newQuantityToRelease < $oldQuantityToRelease) {
                $quantityToRemove = $oldQuantityToRelease - $newQuantityToRelease;
                $remainingToRemove = $quantityToRemove;

                $allocations = $transferItem->inventory_allocations()->orderBy('expiry_date', 'desc')->get();

                foreach ($allocations as $allocation) {
                    if ($remainingToRemove <= 0) break;

                    if ($isFromWarehouse) {
                        // Handle warehouse inventory
                        $inventory = InventoryItem::where('product_id', $allocation->product_id)
                            ->where('warehouse_id', $allocation->warehouse_id)
                            ->where('batch_number', $allocation->batch_number)
                            ->where('expiry_date', $allocation->expiry_date)
                            ->first();

                        $restoreQty = min($allocation->allocated_quantity, $remainingToRemove);

                        if ($inventory) {
                            $inventory->quantity += $restoreQty;
                            $inventory->save();
                        } else {
                            InventoryItem::create([
                                'product_id'   => $allocation->product_id,
                                'warehouse_id' => $allocation->warehouse_id,
                                'location_id'  => $allocation->location_id,
                                'batch_number' => $allocation->batch_number,
                                'uom'          => $allocation->uom,
                                'barcode'      => $allocation->barcode,
                                'expiry_date'  => $allocation->expiry_date,
                                'quantity'     => $restoreQty
                            ]);
                        }
                    } else {
                        // Handle facility inventory
                        $facilityInventory = FacilityInventory::where('facility_id', $sourceId)
                            ->where('product_id', $allocation->product_id)
                            ->first();

                        if ($facilityInventory) {
                            $facilityInventoryItem = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                                ->where('batch_number', $allocation->batch_number)
                                ->where('expiry_date', $allocation->expiry_date)
                                ->first();

                            $restoreQty = min($allocation->allocated_quantity, $remainingToRemove);

                            if ($facilityInventoryItem) {
                                $facilityInventoryItem->quantity += $restoreQty;
                                $facilityInventoryItem->save();
                            } else {
                                FacilityInventoryItem::create([
                                    'facility_inventory_id' => $facilityInventory->id,
                                    'batch_number' => $allocation->batch_number,
                                    'uom'          => $allocation->uom,
                                    'barcode'      => $allocation->barcode,
                                    'expiry_date'  => $allocation->expiry_date,
                                    'quantity'     => $restoreQty
                                ]);
                            }
                        }
                    }

                    // Use update_quantity if it's set (not zero), otherwise use allocated_quantity
                    $effectiveQuantity = ($allocation->update_quantity ?? 0) !== 0 ? $allocation->update_quantity : $allocation->allocated_quantity;
                    
                    if ($effectiveQuantity <= $remainingToRemove) {
                        $remainingToRemove -= $effectiveQuantity;
                        $allocation->delete();
                    } else {
                        // Update the update_quantity field instead of allocated_quantity
                        $newUpdateQuantity = $effectiveQuantity - $remainingToRemove;
                        $allocation->update_quantity = $newUpdateQuantity;
                        $allocation->save();
                        $remainingToRemove = 0;
                    }
                }

                $transferItem->quantity_to_release = $newQuantityToRelease;
                $transferItem->save();

                // Dispatch inventory updated // event
                // event(new InventoryUpdated($sourceId));

                DB::commit();
                return response()->json('Quantity to release decreased successfully', 200);
            }

            // Case 2: Increase
            if ($newQuantityToRelease > $oldQuantityToRelease) {
                $quantityToAdd = $newQuantityToRelease - $oldQuantityToRelease;
                $remainingToAllocate = $quantityToAdd;

                if ($isFromWarehouse) {
                    // Handle warehouse inventory
                    $inventoryItems = InventoryItem::where('product_id', $transferItem->product_id)
                        ->where('warehouse_id', $sourceId)
                        ->where('quantity', '>', 0)
                        ->where(function($query) {
                            $query->where('expiry_date', '>', \Carbon\Carbon::now())
                                  ->orWhereNull('expiry_date');
                        })
                        ->orderBy('expiry_date', 'asc')
                        ->get();

                    if ($inventoryItems->isEmpty()) {
                        DB::rollBack();
                        return response()->json('No inventory available for this product in the warehouse', 500);
                    }

                    foreach ($inventoryItems as $inventory) {
                        if ($remainingToAllocate <= 0) break;

                        $allocQty = min($inventory->quantity, $remainingToAllocate);

                        $existingAllocation = $transferItem->inventory_allocations()
                            ->where('batch_number', $inventory->batch_number)
                            ->where('expiry_date', $inventory->expiry_date)
                            ->first();

                        if ($existingAllocation) {
                            $existingAllocation->allocated_quantity += $allocQty;
                            $existingAllocation->save();
                        } else {
                            $transferItem->inventory_allocations()->create([
                                'product_id'       => $inventory->product_id,
                                'warehouse_id'     => $inventory->warehouse_id,
                                'location_id'      => $inventory->location_id,
                                'batch_number'     => $inventory->batch_number,
                                'uom'              => $inventory->uom,
                                'barcode'          => $inventory->barcode ?? null,
                                'expiry_date'      => $inventory->expiry_date,
                                'allocated_quantity' => $allocQty,
                                'allocation_type'  => $transfer->transfer_type,
                                'unit_cost'        => $inventory->unit_cost,
                                'total_cost'       => $inventory->unit_cost * $allocQty,
                                'notes'            => 'Allocated from warehouse inventory ID: ' . $inventory->id
                            ]);
                        }

                        $inventory->quantity -= $allocQty;
                        $inventory->save();
                        $remainingToAllocate -= $allocQty;
                    }
                } else {
                    // Handle facility inventory
                    $facilityInventory = FacilityInventory::where('facility_id', $sourceId)
                        ->where('product_id', $transferItem->product_id)
                        ->first();

                    if (!$facilityInventory) {
                        DB::rollBack();
                        return response()->json('No inventory available for this product in the facility', 500);
                    }

                    $facilityInventoryItems = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                        ->where('quantity', '>', 0)
                        ->where(function($query) {
                            $query->where('expiry_date', '>', \Carbon\Carbon::now())
                                  ->orWhereNull('expiry_date');
                        })
                        ->orderBy('expiry_date', 'asc')
                        ->get();

                    if ($facilityInventoryItems->isEmpty()) {
                        DB::rollBack();
                        return response()->json('No inventory available for this product in the facility', 500);
                    }

                    foreach ($facilityInventoryItems as $facilityItem) {
                        if ($remainingToAllocate <= 0) break;

                        $allocQty = min($facilityItem->quantity, $remainingToAllocate);

                        $existingAllocation = $transferItem->inventory_allocations()
                            ->where('batch_number', $facilityItem->batch_number)
                            ->where('expiry_date', $facilityItem->expiry_date)
                            ->first();

                        if ($existingAllocation) {
                            $existingAllocation->allocated_quantity += $allocQty;
                            $existingAllocation->save();
                        } else {
                            $transferItem->inventory_allocations()->create([
                                'product_id'       => $facilityInventory->product_id,
                                'facility_id'      => $sourceId,
                                'batch_number'     => $facilityItem->batch_number,
                                'uom'              => $facilityItem->uom,
                                'barcode'          => $facilityItem->barcode ?? null,
                                'expiry_date'      => $facilityItem->expiry_date,
                                'allocated_quantity' => $allocQty,
                                'allocation_type'  => $transfer->transfer_type,
                                'unit_cost'        => 0, // Facility items might not have unit cost
                                'total_cost'       => 0,
                                'notes'            => 'Allocated from facility inventory ID: ' . $facilityItem->id
                            ]);
                        }

                        $facilityItem->quantity -= $allocQty;
                        $facilityItem->save();
                        $remainingToAllocate -= $allocQty;
                    }
                }

                // Final adjustment
                $totalAllocated = $transferItem->inventory_allocations()->sum('allocated_quantity');
                if ($totalAllocated < $newQuantityToRelease) {
                    $difference = $newQuantityToRelease - $totalAllocated;
                    $lastAllocation = $transferItem->inventory_allocations()->latest()->first();

                    if ($lastAllocation) {
                        $lastAllocation->allocated_quantity += $difference;
                        $lastAllocation->save();

                        if ($isFromWarehouse) {
                            $inventory = InventoryItem::where('product_id', $lastAllocation->product_id)
                                ->where('warehouse_id', $lastAllocation->warehouse_id)
                                ->where('batch_number', $lastAllocation->batch_number)
                                ->where('expiry_date', $lastAllocation->expiry_date)
                                ->first();

                            if ($inventory) {
                                $inventory->quantity -= $difference;
                                $inventory->save();
                            }
                        } else {
                            $facilityInventory = FacilityInventory::where('facility_id', $sourceId)
                                ->where('product_id', $lastAllocation->product_id)
                                ->first();

                            if ($facilityInventory) {
                                $facilityItem = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                                    ->where('batch_number', $lastAllocation->batch_number)
                                    ->where('expiry_date', $lastAllocation->expiry_date)
                                    ->first();

                                if ($facilityItem) {
                                    $facilityItem->quantity -= $difference;
                                    $facilityItem->save();
                                }
                            }
                        }
                    }
                }

                if ($remainingToAllocate > 0) {
                    DB::rollBack();
                    $sourceType = $isFromWarehouse ? 'warehouse' : 'facility';
                    return response()->json("Insufficient inventory in {$sourceType}. Could only allocate " . ($quantityToAdd - $remainingToAllocate) . ' out of ' . $quantityToAdd, 500);
                }

                $transferItem->quantity_to_release = $newQuantityToRelease;
                $transferItem->save();

                // Dispatch inventory updated // event
                // event(new InventoryUpdated($sourceId));

                DB::commit();
                return response()->json('Quantity to release updated successfully', 200);
            }

            // No change
            DB::commit();
            return response()->json('No change in quantity to release', 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Save back orders for a transfer item with detailed issue types
     */
    public function backorder(Request $request)
    {
        try {
            $request->validate([
                'transfer_item_id' => 'required|exists:transfer_items,id',
                'differences' => 'required|array',
                'received_quantity' => 'required|numeric|min:0',
                'differences.*.inventory_allocation_id' => 'required|exists:inventory_allocations,id',
                'differences.*.quantity' => 'required|numeric|min:0',
                'differences.*.status' => 'required|in:Missing,Damaged,Expired,Lost,Low Quality',
                'differences.*.notes' => 'nullable|string',
                'differences.*.id' => 'nullable|exists:packing_list_differences,id',
                'deleted_differences' => 'nullable|array',
                'deleted_differences.*' => 'exists:packing_list_differences,id'
            ]);

            return DB::transaction(function () use ($request) {
                $transferItem = TransferItem::with('transfer.facility:id,name','inventory_allocations')->find($request->transfer_item_id);
                $transferItem->received_quantity = $request->received_quantity;
                $transferItem->save();
                
                if ($transferItem->transfer->to_facility_id != auth()->user()->facility_id) {
                    return response()->json('You are not authorized to record differences for this transfer.', 403);
                }

                // Find or create a single BackOrder for the entire transfer
                $hasDifferenceItems = collect($request->differences)
                    ->filter(function($item) { return !empty($item); })
                    ->isNotEmpty();
                $backOrder = null;
                if ($hasDifferenceItems) {
                    $backOrder = BackOrder::firstOrCreate(
                        ['transfer_id' => $transferItem->transfer_id],
                        [
                            'transfer_id' => $transferItem->transfer_id,
                            'back_order_date' => now()->toDateString(),
                            'created_by' => auth()->user()->id,
                            'source_type' => 'transfer',
                            'reported_by' => $transferItem->transfer->toFacility->name ?? 'Unknown Facility',
                        ]
                    );
                }

                // Process deleted differences first
                if ($request->has('deleted_differences') && !empty($request->deleted_differences)) {
                    PackingListDifference::whereIn('id', $request->deleted_differences)->delete();
                }

                // Process differences (create new ones or update existing ones)
                foreach ($request->differences as $differenceData) {
                    $inventoryAllocation = InventoryAllocation::where('id', $differenceData['inventory_allocation_id'])
                        ->where('transfer_item_id', $transferItem->id)
                        ->first();
                    if (!$inventoryAllocation) {
                        return response()->json('Invalid inventory allocation specified.', 500);
                    }
                    if ($differenceData['quantity'] > $inventoryAllocation->allocated_quantity) {
                        return response()->json('Difference quantity exceeds allocated quantity for batch ' . $inventoryAllocation->batch_number, 500);
                    }
                    if (isset($differenceData['id'])) {
                        $difference = PackingListDifference::find($differenceData['id']);
                        if ($difference) {
                            $difference->update([
                                'quantity' => $differenceData['quantity'],
                                'notes' => $differenceData['notes'],
                                'status' => $differenceData['status'],
                                'back_order_id' => $backOrder ? $backOrder->id : null,
                            ]);
                        }
                    } else {
                        PackingListDifference::create([
                            'product_id' => $transferItem->product_id,
                            'inventory_allocation_id' => $inventoryAllocation->id,
                            'quantity' => $differenceData['quantity'],
                            'notes' => $differenceData['notes'],
                            'status' => $differenceData['status'],
                            'back_order_id' => $backOrder ? $backOrder->id : null,
                        ]);
                    }
                }
                
                // Update BackOrder totals if it exists
                if ($backOrder) {
                    $backOrder->updateTotals();
                }
                
                return response()->json('Differences have been recorded successfully', 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function saveBackOrders(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'packing_list_differences' => 'required|array',
                'packing_list_differences.*.quantity' => 'required|numeric|min:0',
                'packing_list_differences.*.status' => 'required|in:Missing,Damaged,Expired,Lost,Low Quality',
                'packing_list_differences.*.notes' => 'nullable|string',
                'packing_list_differences.*.transfer_item_id' => 'required|exists:transfer_items,id',
            ]);

            $transfer = Transfer::find($request->transfer_id);
            if(!$transfer) {
                return response()->json('Transfer not found', 500);
            }

            // Find or create BackOrder for this transfer
            $backOrder = BackOrder::where('transfer_id', $request->transfer_id)->first();

            if(!$backOrder) {
                $backOrder = BackOrder::create([
                    'transfer_id' => $request->transfer_id,
                    'back_order_date' => now()->toDateString(),
                    'created_by' => auth()->user()->id,
                    'source_type' => 'transfer',
                    'reported_by' => $transfer->toFacility->name ?? 'Unknown Facility',
                    'total_items' => 0,
                    'total_quantity' => 0,
                ]);
            }

            // Process packing list differences
            $totalQuantity = 0;
            $totalItems = 0;
            
            foreach ($request->packing_list_differences as $differenceData) {
                
                $transferItem = TransferItem::find($differenceData['transfer_item_id']);
                if(!$transferItem) {
                    throw new \Exception('Transfer item not found: ' . $differenceData['transfer_item_id']);
                    break;
                }
                
                if (isset($differenceData['id']) && $differenceData['id']) {
                    // Update existing difference
                    $difference = PackingListDifference::find($differenceData['id']);
                    if ($difference) {
                        $difference->update([
                            'back_order_id' => $backOrder->id,
                            'product_id' => $transferItem->product_id,
                            'inventory_allocation_id' => $differenceData['inventory_allocation_id'] ?? null,
                            'quantity' => $differenceData['quantity'],
                            'status' => $differenceData['status'],
                            'notes' => $differenceData['notes'] ?? null,
                        ]);
                    }
                } else {
                    // Create new difference
                    $difference = PackingListDifference::create([
                        'back_order_id' => $backOrder->id,
                        'product_id' => $transferItem->product_id,
                        'inventory_allocation_id' => $differenceData['inventory_allocation_id'] ?? null,
                        'quantity' => $differenceData['quantity'],
                        'status' => $differenceData['status'],
                        'notes' => $differenceData['notes'] ?? null,
                    ]);
                }
                
                $totalQuantity += $differenceData['quantity'];
                $totalItems++;
            }

            // Update BackOrder totals
            $backOrder->update([
                'total_items' => $totalItems,
                'total_quantity' => $totalQuantity,
            ]);
            
            // // event(new \App\// events\InventoryUpdated($transfer->from_facility_id));

            DB::commit();
            return response()->json('Back orders saved successfully', 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Helper method to update transfer status based on item completion
     */
    private function updateTransferStatusIfNeeded($transfer)
    {
        $allItemsProcessed = $transfer->items->every(function ($item) {
            $missingQuantity = $item->quantity_to_release - ($item->received_quantity ?? 0);
            $existingBackOrders = $item->inventory_allocations()
                ->whereHas('backorders')
                ->with('backorders')
                ->get()
                ->flatMap(function($allocation) {
                    return $allocation->backorders;
                })
                ->sum('quantity');
            
            return $missingQuantity <= $existingBackOrders;
        });

        if ($allItemsProcessed && $transfer->status === 'shipped') {
            $transfer->status = 'received';
            $transfer->save();
        }
    }
    
    /**
     * Get inventories based on source type and ID
     */
    public function getInventories(Request $request)
    {
        try {
            // Get facility inventories directly with DB query
            $products = Product::whereHas('inventory', function($query) {
                $query->where('facility_id', auth()->user()->facility_id)
                      ->whereHas('items', function($subQuery) {
                          $subQuery->where('quantity', '>', 0);
                      });
            })
                ->select('id','name')
                ->get();
                
            return response()->json($products, 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            return response()->json($th->getMessage(), 500);
        }
    }

    public function updateQuantity(Request $request){
        try {
            DB::beginTransaction();

            $request->validate([
                'allocation_id'  => 'required|exists:inventory_allocations,id',
                'quantity' => 'required|numeric'
            ]);

            $allocation = InventoryAllocation::findOrFail($request->allocation_id);
            $transferItem = $allocation->transfer_item;
            $transfer = $transferItem->transfer;

            // Update the specific allocation quantity
            $oldUpdatedQuantity = $allocation->updated_quantity ?? 0;
            $newUpdatedQuantity = (int) ceil($request->quantity);
            
            // Calculate the difference in updated quantity
            $quantityDifference = $newUpdatedQuantity - $oldUpdatedQuantity;
            
            if ($quantityDifference == 0) {
                DB::commit();
                return response()->json('No change in quantity', 200);
            }

            // Always use from_facility_id as the source for FacilityInventory
            $isFromWarehouse = false;
            $sourceId = $transfer->from_facility_id;

            // Case 1: Decrease allocation quantity
            if ($quantityDifference < 0) {
                $quantityToRestore = abs($quantityDifference);
                
                // Restore quantity to facility inventory
                if ($isFromWarehouse) {
                    // Handle warehouse inventory
                    $inventory = InventoryItem::where('product_id', $allocation->product_id)
                        ->where('warehouse_id', $allocation->warehouse_id)
                        ->where('batch_number', $allocation->batch_number)
                        ->where('expiry_date', $allocation->expiry_date)
                        ->first();

                    if ($inventory) {
                        $inventory->quantity += $quantityToRestore;
                        $inventory->save();
                    } else {
                        InventoryItem::create([
                            'product_id'   => $allocation->product_id,
                            'warehouse_id' => $allocation->warehouse_id,
                            'location_id'  => $allocation->location_id,
                            'batch_number' => $allocation->batch_number,
                            'uom'          => $allocation->uom,
                            'barcode'      => $allocation->barcode,
                            'expiry_date'  => $allocation->expiry_date,
                            'quantity'     => $quantityToRestore
                        ]);
                    }
                } else {
                    // Handle facility inventory
                    $facilityInventory = FacilityInventory::where('facility_id', $sourceId)
                        ->where('product_id', $allocation->product_id)
                        ->first();

                    if ($facilityInventory) {
                        $facilityInventoryItem = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                            ->where('batch_number', $allocation->batch_number)
                            ->where('expiry_date', $allocation->expiry_date)
                            ->first();

                        if ($facilityInventoryItem) {
                            $facilityInventoryItem->quantity += $quantityToRestore;
                            $facilityInventoryItem->save();
                        } else {
                            FacilityInventoryItem::create([
                                'facility_inventory_id' => $facilityInventory->id,
                                'batch_number' => $allocation->batch_number,
                                'uom'          => $allocation->uom,
                                'barcode'      => $allocation->barcode,
                                'expiry_date'  => $allocation->expiry_date,
                                'quantity'     => $quantityToRestore
                            ]);
                        }
                    }
                }

                // Update allocation updated_quantity
                $allocation->updated_quantity = $newUpdatedQuantity;
                $allocation->save();

                // Update transfer item total quantity (sum of original allocated_quantity + updated_quantity for all allocations)
                $totalQuantity = $transferItem->inventory_allocations->sum(function($alloc) {
                    return $alloc->allocated_quantity + ($alloc->updated_quantity ?? 0);
                });
                $transferItem->quantity_to_release = $totalQuantity;
                $transferItem->save();

                // Dispatch inventory updated // event
                // // event(new InventoryUpdated($sourceId));

                DB::commit();
                return response()->json('Allocation quantity decreased successfully', 200);
            }

            // Case 2: Increase allocation quantity
            if ($quantityDifference > 0) {
                $quantityToAllocate = $quantityDifference;
                
                // Check if we have enough inventory
                if ($isFromWarehouse) {
                    // Handle warehouse inventory
                    $inventory = InventoryItem::where('product_id', $allocation->product_id)
                        ->where('warehouse_id', $allocation->warehouse_id)
                        ->where('batch_number', $allocation->batch_number)
                        ->where('expiry_date', $allocation->expiry_date)
                        ->where('quantity', '>=', $quantityToAllocate)
                        ->first();

                    if (!$inventory) {
                        DB::rollBack();
                        return response()->json('Insufficient inventory available for this allocation', 400);
                    }

                    $inventory->quantity -= $quantityToAllocate;
                    $inventory->save();
                } else {
                    // Handle facility inventory
                    $facilityInventory = FacilityInventory::where('facility_id', $sourceId)
                        ->where('product_id', $allocation->product_id)
                        ->first();

                    if (!$facilityInventory) {
                        DB::rollBack();
                        return response()->json('No inventory available for this product in the facility', 400);
                    }

                    $facilityInventoryItem = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                        ->where('batch_number', $allocation->batch_number)
                        ->where('expiry_date', $allocation->expiry_date)
                        ->where('quantity', '>=', $quantityToAllocate)
                        ->first();

                    if (!$facilityInventoryItem) {
                        DB::rollBack();
                        return response()->json('Insufficient inventory available for this allocation', 400);
                    }

                    $facilityInventoryItem->quantity -= $quantityToAllocate;
                    $facilityInventoryItem->save();
                }

                // Update allocation updated_quantity
                $allocation->updated_quantity = $newUpdatedQuantity;
                $allocation->save();

                // Update transfer item total quantity (sum of original allocated_quantity + updated_quantity for all allocations)
                $totalQuantity = $transferItem->inventory_allocations->sum(function($alloc) {
                    return $alloc->allocated_quantity + ($alloc->updated_quantity ?? 0);
                });
                $transferItem->quantity_to_release = $totalQuantity;
                $transferItem->save();

                // Dispatch inventory updated // event
                // // event(new InventoryUpdated($sourceId));

                DB::commit();
                return response()->json('Allocation quantity increased successfully', 200);
            }

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    
    /**
     * Delete a specific back order record
     */
    public function deleteBackOrder(Request $request)
    {
        try {
            $request->validate([
                'backorder_id' => 'required|exists:back_orders,id',
            ]);

            $backorder = BackOrder::findOrFail($request->backorder_id);
            
            // Verify user has permission to delete back orders for this transfer
            if ($backorder->transfer && !in_array($backorder->transfer->status, ['pending', 'shipped', 'received'])) {
                return response()->json(['error' => 'Cannot delete back orders for transfers with this status'], 400);
            }

            $backorder->delete();

            if ($backorder->transfer) {
                // event(new InventoryUpdated($backorder->transfer->from_facility_id));
            }

            return response()->json('Back order deleted successfully', 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Change transfer status with proper permissions and workflow validation
     */
    public function changeStatus(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'status' => 'required|string|in:pending,reviewed,approved,in_process,dispatched,delivered,received,rejected'
            ]);

            $transfer = Transfer::findOrFail($request->transfer_id);
            $newStatus = $request->status;
            $oldStatus = $transfer->status;
            $user = auth()->user();

            // Define status progression order
            $statusOrder = ['pending', 'reviewed', 'approved', 'in_process', 'dispatched', 'delivered', 'received','rejected'];
            $currentStatusIndex = array_search($transfer->status, $statusOrder);
            $newStatusIndex = array_search($newStatus, $statusOrder);

            // Validate status progression (can only move forward, except for certain cases)
            if ($newStatusIndex <= $currentStatusIndex && $newStatus !== $transfer->status) {
                DB::rollBack();
                return response()->json('Cannot move backwards in transfer workflow', 400);
            }

            // Permission checks based on status
            switch ($newStatus) {
                case 'reviewed':
                    if ($transfer->status !== 'pending') {
                        DB::rollBack();
                        return response()->json('Transfer must be pending to review', 400);
                    }
                    $transfer->reviewed_at = now();
                    $transfer->reviewed_by = $user->id;
                    break;

                case 'approved':
                    if ($transfer->status !== 'reviewed') {
                        DB::rollBack();
                        return response()->json('Transfer must be reviewed to approve', 400);
                    }
                    $transfer->approved_at = now();
                    $transfer->approved_by = $user->id;
                    break;

                case 'rejected':
                    if ($transfer->status !== 'reviewed') {
                        DB::rollBack();
                        return response()->json('Transfer must be reviewed to reject', 400);
                    }
                    $transfer->rejected_at = now();
                    $transfer->rejected_by = $user->id;
                    break;

                case 'in_process':
                    // Can be done by from warehouse/facility staff
                    if ($user->warehouse_id !== $transfer->from_warehouse_id && 
                        $user->facility_id !== $transfer->from_facility_id) {
                        DB::rollBack();
                        return response()->json('You can only process transfers from your warehouse/facility', 403);
                    }
                    if ($transfer->status !== 'approved') {
                        DB::rollBack();
                        return response()->json('Transfer must be approved to process', 400);
                    }
                    $transfer->processed_at = now();
                    $transfer->processed_by = $user->id;
                    break;

                case 'dispatched':
                    // Can be done by from warehouse/facility staff
                    if ($user->warehouse_id !== $transfer->from_warehouse_id && 
                        $user->facility_id !== $transfer->from_facility_id) {
                        DB::rollBack();
                        return response()->json('You can only dispatch transfers from your warehouse/facility', 403);
                    }
                    if ($transfer->status !== 'in_process') {
                        DB::rollBack();
                        return response()->json('Transfer must be in process to dispatch', 400);
                    }
                    $transfer->dispatched_at = now();
                    $transfer->dispatched_by = $user->id;
                    break;

                case 'delivered':
                    // Can be done by to warehouse/facility staff
                    if ($user->warehouse_id !== $transfer->to_warehouse_id && 
                        $user->facility_id !== $transfer->to_facility_id) {
                        DB::rollBack();
                        return response()->json('You can only mark transfers as delivered to your warehouse/facility', 403);
                    }
                    if ($transfer->status !== 'dispatched') {
                        DB::rollBack();
                        return response()->json('Transfer must be dispatched to deliver', 400);
                    }
                    $transfer->delivered_at = now();
                    $transfer->delivered_by = $user->id;
                    break;

                case 'received':
                    // Can be done by to warehouse/facility staff
                    if ($user->warehouse_id !== $transfer->to_warehouse_id && 
                        $user->facility_id !== $transfer->to_facility_id) {
                        DB::rollBack();
                        return response()->json('You can only receive transfers to your warehouse/facility', 403);
                    }
                    if ($transfer->status !== 'delivered') {
                        DB::rollBack();
                        return response()->json('Transfer must be delivered to receive', 400);
                    }
            
                foreach ($transfer->items as $item) {
                    // Debug information for this item
                    
                    foreach ($item->inventory_allocations as $allocation) {
                        // Use the actual received_quantity for this allocation
                        $receivedQuantity = $allocation->received_quantity ?? 0;
                        
                        // Use updated_quantity if it's set (not null and greater than 0), otherwise use allocated_quantity
                        $effectiveQuantity = ($allocation->updated_quantity !== null && $allocation->updated_quantity > 0) ? $allocation->updated_quantity : $allocation->allocated_quantity;
                        
                        // Validate that received quantity doesn't exceed effective quantity
                        if ($receivedQuantity > $effectiveQuantity) {
                            DB::rollback();
                            return response()->json('Received quantity cannot exceed effective quantity', 500);
                        }
                        
                        $finalQuantity = $receivedQuantity;
                        
                        $inventory = FacilityInventory::where('facility_id', $transfer->to_facility_id)
                            ->where('product_id', $allocation->product_id)
                            ->first();
    
                        if($inventory){
                            $inventoryItem = $inventory->items()->where('batch_number', $allocation->batch_number)->first();
                            if($inventoryItem){
                                $inventoryItem->increment('quantity', $finalQuantity);
                            }else{
                                $inventory->items()->create([
                                    'product_id' => $allocation->product_id,
                                    'quantity' => $finalQuantity,
                                    'expiry_date' => $allocation->expiry_date,
                                    'batch_number' => $allocation->batch_number,
                                    'barcode' => $allocation->barcode,
                                    'uom' => $allocation->uom,
                                    'unit_cost' => $allocation->unit_cost,
                                    'total_cost' => $allocation->unit_cost * $finalQuantity
                                ]);
                            }
                            
                        }else{
                            $inventory = FacilityInventory::create([
                                'facility_id' => $transfer->to_facility_id,
                                'product_id' => $allocation->product_id
                            ]);
                            $inventory->items()->create([
                                'product_id' => $allocation->product_id,
                                'batch_number' => $allocation->batch_number,
                                'expiry_date' => $allocation->expiry_date,
                                'quantity' => $finalQuantity,
                                'barcode' => $allocation->barcode,
                                'uom' => $allocation->uom,
                                'unit_cost' => $allocation->unit_cost,
                                'total_cost' => $allocation->unit_cost * $finalQuantity
                            ]);
                        }
                        
                        // Record facility inventory movement for received quantity
                        // if ($finalQuantity > 0) {
                        //     FacilityInventoryMovementService::recordTransferReceived(
                        //         $transfer,
                        //         $item,
                        //         $finalQuantity,
                        //         $allocation->batch_number,
                        //         $allocation->expiry_date,
                        //         $allocation->barcode
                        //     );
                        // }
                    }
                }
                
                // Update transfer status to received
                $transfer->status = 'received';
                $transfer->received_at = Carbon::now();
                $transfer->received_by = auth()->user()->id;
                $transfer->save();
                
                // Dispatch // event for status change
                // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, auth()->id()));
                    break;

                default:
                    DB::rollBack();
                    return response()->json('Invalid status', 400);
            }

            // Update the status
            $transfer->status = $newStatus;
            $transfer->save();

            // Dispatch // event for real-time updates
            // // event(new TransferStatusChanged($transfer, $oldStatus, $newStatus, $user->id));

            DB::commit();
            return response()->json('Transfer status updated successfully', 200);

        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }
    
    /**
     * Receive a back order
     */
    public function receiveBackOrder(Request $request){
        try {
            DB::beginTransaction();
            
            $request->validate([
                'backorder' => 'required',
                'quantity' => 'required|numeric|min:1',
            ]);
            
            $backorderData = $request->backorder;
            $receivedQuantity = $request->quantity;
            
            // Find the backorder record
            $backorder = BackOrder::findOrFail($backorderData['id']);
            
            // Find the transfer item through the differences
            $difference = PackingListDifference::where('back_order_id', $backorder->id)->first();
            if (!$difference || !$difference->inventoryAllocation) {
                throw new \Exception('Back order difference or inventory allocation not found');
            }
            
            $transferItem = $difference->inventoryAllocation->transfer_item;
            if (!$transferItem) {
                throw new \Exception('Transfer item not found for this back order');
            }
            
            // Update the received quantity of the transfer item
            $transferItem->received_quantity += $receivedQuantity;
            $transferItem->save();
            
            // Deduct the received quantity from the packing list difference
            $difference->quantity -= $receivedQuantity;
            
            // If quantity becomes zero, mark as finalized
            if ($difference->quantity <= 0) {
                $difference->update(['finalized' => 'Received']);
            } else {
                // Otherwise, save the updated quantity
                $difference->save();
            }
            
            // Get the warehouse ID from the transfer
            $transfer = Transfer::with('toWarehouse')->findOrFail($transferItem->transfer_id);
            $warehouseId = $transfer->to_warehouse_id;
            
            if (!$warehouseId) {
                throw new \Exception('No destination warehouse found for this transfer');
            }
            
            // Check if inventory exists for this product in the warehouse
            $inventory = Inventory::where('warehouse_id', $warehouseId)
                ->where('product_id', $backorder->product_id)
                ->where('batch_number', $transferItem->batch_number)
                ->where('expiry_date', $transferItem->expire_date)
                ->first();
            
            if ($inventory) {
                // Update existing inventory
                $inventory->quantity += $receivedQuantity;
                $inventory->save();
                ReceivedQuantity::create([
                    'quantity' => $receivedQuantity,
                    'received_by' => auth()->id(),
                    'received_at' => now(),
                    'product_id' => $backorder->product_id,
                    'warehouse_id' => $warehouseId,
                    'transfer_id' => $transfer->id,
                    'expiry_date' => $transferItem->expire_date,
                    'uom' => $transferItem->uom,
                    'barcode' => $transferItem->barcode,
                    'batch_number' => $transferItem->batch_number,
                    'unit_cost' => $transferItem->unit_cost,
                    'total_cost' => $transferItem->unit_cost
                ]);
            } else {
                // Create new inventory record
                $inventory = Inventory::create([
                    'warehouse_id' => $warehouseId,
                    'product_id' => $backorder->product_id,
                    'batch_number' => $transferItem->batch_number,
                    'expiry_date' => $transferItem->expire_date,
                    'barcode' => $transferItem->barcode,
                    'quantity' => $receivedQuantity,
                    'created_by' => auth()->id(),
                    'updated_by' => auth()->id(),
                ]);
                ReceivedQuantity::create([
                    'transfer_id' => $transfer->id,
                    'quantity' => $receivedQuantity,
                    'received_by' => auth()->id(),
                    'received_at' => now(),
                    'product_id' => $backorder->product_id,
                    'expiry_date' => $transferItem->expire_date,
                    'uom' => $transferItem->uom,
                    'warehouse_id' => $warehouseId,
                    'unit_cost' => $transferItem->unit_cost,
                    'total_cost' => $transferItem->unit_cost * $receivedQuantity,
                    'barcode' => $transferItem->barcode,
                    'batch_number' => $transferItem->batch_number,
                ]);
            }
            
            // Create backorder history record
            BackOrderHistory::create([
                'packing_list_id' => null, // No packing list for transfer backorders
                'transfer_id' => $transfer->id,
                'product_id' => $backorder->product_id,
                'quantity' => $receivedQuantity,
                'status' => "Received", // 'Missing' or 'Damaged'
                'note' => 'Backorder received and added to inventory',
                'performed_by' => auth()->id()
            ]);
            
            DB::commit();
            
            // Dispatch // event for real-time inventory updates
            $inventoryData = [
                'product_id' => $backorder->product_id,
                'warehouse_id' => $warehouseId,
                'quantity' => $inventory->quantity,
                'batch_number' => $transferItem->batch_number,
                'expiry_date' => $transferItem->expire_date,
                'action' => 'received',
                'source' => 'backorder'
            ];
            // event(new \App\// events\InventoryUpdated($inventoryData));
            return response()->json([
                'message' => 'Backorder received successfully'
            ], 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json([
                'error' => $th->getMessage()
            ], 500);
        }
    }
    
    /**
     * Show the form for editing the specified transfer
     */
    public function edit($id)
    {
        try {
            $transfer = Transfer::with(['items.product', 'fromWarehouse', 'toWarehouse', 'fromFacility', 'toFacility'])
                ->findOrFail($id);
            
            // Check authorization
            $user = auth()->user();
            $canEdit = false;
            
            if ($transfer->from_facility_id == $user->facility_id || 
                $transfer->to_facility_id == $user->facility_id ||
                $transfer->from_warehouse_id == $user->warehouse_id ||
                $transfer->to_warehouse_id == $user->warehouse_id) {
                $canEdit = true;
            }
            
            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                $canEdit = true;
            }
            
            if (!$canEdit) {
                return redirect()->back()->with('error', 'You are not authorized to edit this transfer');
            }
            
            // Only allow editing if transfer is in pending status
            if ($transfer->status !== 'pending') {
                return redirect()->back()->with('error', 'Cannot edit transfer that is not in pending status');
            }
            
            $warehouses = Warehouse::select('id', 'name')->get();
            $facilities = Facility::select('id', 'name')->get();
            
            return inertia('Transfer/Edit', [
                'transfer' => $transfer,
                'warehouses' => $warehouses,
                'facilities' => $facilities
            ]);
        } catch (\Throwable $th) {
            return redirect()->back()->with('error', 'Transfer not found');
        }
    }
    
    /**
     * Update the specified transfer
     */
    public function update(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            
            $transfer = Transfer::findOrFail($id);
            
            // Check authorization and status
            $user = auth()->user();
            $canUpdate = false;
            
            if ($transfer->from_facility_id == $user->facility_id || 
                $transfer->to_facility_id == $user->facility_id ||
                $transfer->from_warehouse_id == $user->warehouse_id ||
                $transfer->to_warehouse_id == $user->warehouse_id) {
                $canUpdate = true;
            }
            
            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                $canUpdate = true;
            }
            
            if (!$canUpdate) {
                return response()->json(['error' => 'You are not authorized to update this transfer'], 403);
            }
            
            if ($transfer->status !== 'pending') {
                return response()->json(['error' => 'Cannot update transfer that is not in pending status'], 400);
            }
            
            $request->validate([
                'transfer_date' => 'required|date',
                'notes' => 'nullable|string',
                'expected_date' => 'nullable|date'
            ]);
            
            $transfer->update([
                'transfer_date' => $request->transfer_date,
                'notes' => $request->notes,
                'expected_date' => $request->expected_date
            ]);
            
            DB::commit();
            return response()->json('Transfer updated successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Remove the specified transfer
     */
    public function destroy($id)
    {
        try {
            DB::beginTransaction();
            
            $transfer = Transfer::findOrFail($id);
            
            // Check authorization
            $user = auth()->user();
            $canDelete = false;
            
            if ($transfer->from_facility_id == $user->facility_id || 
                $transfer->to_facility_id == $user->facility_id ||
                $transfer->from_warehouse_id == $user->warehouse_id ||
                $transfer->to_warehouse_id == $user->warehouse_id) {
                $canDelete = true;
            }
            
            if ($user->hasRole('admin') || $user->hasRole('super_admin')) {
                $canDelete = true;
            }
            
            if (!$canDelete) {
                return response()->json(['error' => 'You are not authorized to delete this transfer'], 403);
            }
            
            // Only allow deletion if transfer is in pending status
            if ($transfer->status !== 'pending') {
                return response()->json(['error' => 'Cannot delete transfer that is not in pending status'], 400);
            }
            
            // Restore allocated inventory
            foreach ($transfer->items as $item) {
                foreach ($item->inventory_allocations as $allocation) {
                    if ($transfer->from_warehouse_id) {
                        // Restore warehouse inventory
                        $inventoryItem = InventoryItem::where('product_id', $allocation->product_id)
                            ->where('warehouse_id', $allocation->warehouse_id)
                            ->where('batch_number', $allocation->batch_number)
                            ->where('expiry_date', $allocation->expiry_date)
                            ->first();
                            
                        if ($inventoryItem) {
                            // Use update_quantity if it's set (not zero), otherwise use allocated_quantity
            $effectiveQuantity = ($allocation->update_quantity ?? 0) !== 0 ? $allocation->update_quantity : $allocation->allocated_quantity;
            $inventoryItem->increment('quantity', $effectiveQuantity);
                        }
                    } else {
                        // Restore facility inventory
                        $facilityInventory = FacilityInventory::where('facility_id', $transfer->from_facility_id)
                            ->where('product_id', $allocation->product_id)
                            ->first();
                            
                        if ($facilityInventory) {
                            $facilityItem = FacilityInventoryItem::where('facility_inventory_id', $facilityInventory->id)
                                ->where('batch_number', $allocation->batch_number)
                                ->where('expiry_date', $allocation->expiry_date)
                                ->first();
                                
                            if ($facilityItem) {
                                // Use update_quantity if it's set (not zero), otherwise use allocated_quantity
            $effectiveQuantity = ($allocation->update_quantity ?? 0) !== 0 ? $allocation->update_quantity : $allocation->allocated_quantity;
            $facilityItem->increment('quantity', $effectiveQuantity);
                            }
                        }
                    }
                }
            }
            
            $transfer->delete();
            
            DB::commit();
            return response()->json('Transfer deleted successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Remove back order
     */
    public function removeBackOrder(Request $request)
    {
        try {
            $request->validate([
                'backorder_id' => 'required|exists:back_orders,id',
            ]);

            $backorder = BackOrder::findOrFail($request->backorder_id);
            $backorder->delete();

            return response()->json('Back order removed successfully', 200);

        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Show transfer back order page
     */
    public function transferBackOrder()
    {
        return inertia('Transfer/BackOrder');
    }
    
    /**
     * Transfer liquidate
     */
    public function transferLiquidate(Request $request)
    {
        try {
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.reason' => 'required|string'
            ]);
            
            DB::beginTransaction();
            
            $transfer = Transfer::findOrFail($request->transfer_id);
            
            foreach ($request->items as $item) {
                Liquidate::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                    'created_by' => auth()->id()
                ]);
            }
            
            DB::commit();
            return response()->json('Items liquidated successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Transfer dispose
     */
    public function transferDispose(Request $request)
    {
        try {
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric|min:1',
                'items.*.reason' => 'required|string'
            ]);
            
            DB::beginTransaction();
            
            $transfer = Transfer::findOrFail($request->transfer_id);
            
            foreach ($request->items as $item) {
                Disposal::create([
                    'transfer_id' => $transfer->id,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'reason' => $item['reason'],
                    'created_by' => auth()->id()
                ]);
            }
            
            DB::commit();
            return response()->json('Items disposed successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Restore transfer
     */
    public function restoreTransfer(Request $request)
    {
        try {
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
            ]);
            
            $transfer = Transfer::withTrashed()->findOrFail($request->transfer_id);
            $transfer->restore();
            
            return response()->json('Transfer restored successfully', 200);
            
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Get drivers list
     */
    public function getDrivers()
    {
        try {
            $drivers = DB::table('drivers')->select('id', 'name', 'phone')->get();
            return response()->json($drivers, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    /**
     * Get logistic companies list
     */
    public function getLogisticCompanies()
    {
        try {
            $companies = DB::table('logistic_companies')->select('id', 'name', 'contact_person', 'phone')->get();
            return response()->json($companies, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    

    
    /**
     * Receive transfer
     */
    public function receiveTransfer(Request $request)
    {
        try {
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'received_items' => 'required|array',
                'received_items.*.transfer_item_id' => 'required|exists:transfer_items,id',
                'received_items.*.received_quantity' => 'required|numeric|min:0'
            ]);
            
            DB::beginTransaction();
            
            $transfer = Transfer::findOrFail($request->transfer_id);
            
            // Check if user is authorized to receive this transfer
            $user = auth()->user();
            if ($transfer->to_facility_id !== $user->facility_id && $transfer->to_warehouse_id !== $user->warehouse_id) {
                return response()->json(['error' => 'You are not authorized to receive this transfer'], 403);
            }
            
            foreach ($request->received_items as $item) {
                $transferItem = TransferItem::findOrFail($item['transfer_item_id']);
                $transferItem->received_quantity = $item['received_quantity'];
                $transferItem->save();
            }
            
            // Update transfer status to received
            $transfer->status = 'received';
            $transfer->received_at = now();
            $transfer->received_by = auth()->id();
            $transfer->save();
            
            DB::commit();
            return response()->json('Transfer received successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
    
    public function manageBackOrder()
    {
        // Get transfers with active back orders (those that have non-finalized differences)
        $transfers = Transfer::where('to_facility_id', auth()->user()->facility_id)
            ->whereHas('backOrders', function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            })
            ->with(['backOrders' => function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            }])
            ->get(['id', 'transferID', 'transfer_type'])
            ->filter(function($transfer) {
                return $transfer->backOrders->count() > 0;
            });

        // Get orders with active back orders (those that have non-finalized differences)
        $orders = \App\Models\Order::where('facility_id', auth()->user()->facility_id)
            ->whereHas('backOrders', function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            })
            ->with(['backOrders' => function($query) {
                $query->whereHas('differences', function($q) {
                    $q->whereNull('finalized');
                });
            }])
            ->get(['id', 'order_number', 'order_type'])
            ->filter(function($order) {
                return $order->backOrders->count() > 0;
            });

        return inertia('BackOrder/BackOrder', [
            'transfers' => $transfers,
            'orders' => $orders
        ]);
    }

    /**
     * Mark transfer as delivered with delivery form data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markDelivered(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validate request
            $request->validate([
                'transfer_id' => 'required|exists:transfers,id',
                'received_cartons' => 'required|array',
                'received_cartons.*' => 'required|numeric|min:0',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max per image
            ]);
            
            $transfer = Transfer::with(['dispatch'])->findOrFail($request->transfer_id);
            
            // Validate transfer status
            if ($transfer->status !== 'dispatched') {
                return response()->json('Transfer must be in dispatched status to mark as delivered', 400);
            }
            
            $receivedCartons = $request->received_cartons;
            
            // Handle image uploads
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                foreach ($images as $image) {
                    if ($image->isValid()) {
                        $filename = 'transfer_delivery_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        $image->move(public_path('transfer-delivery-images'), $filename);
                        $imagePaths[] = 'transfer-delivery-images/' . $filename;
                    }
                }
            }
            
            // Update dispatch info with received cartons and images
            if ($transfer->dispatch && count($transfer->dispatch) > 0) {
                foreach ($transfer->dispatch as $dispatch) {
                    if (isset($receivedCartons[$dispatch->id])) {
                        $dispatch->received_cartons = $receivedCartons[$dispatch->id];
                        // Save images to the first dispatch record
                        if (!empty($imagePaths)) {
                            $dispatch->image = json_encode($imagePaths);
                        }
                        $dispatch->save();
                    }
                }
            }
            
            // Update transfer status to delivered
            $transfer->status = 'delivered';
            $transfer->delivered_at = Carbon::now();
            $transfer->delivered_by = auth()->user()->id;
            
            $transfer->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Transfer marked as delivered successfully',
                'data' => [
                    'transfer_id' => $transfer->id,
                    'status' => $transfer->status,
                    'delivered_at' => $transfer->delivered_at,
                    'images_uploaded' => count($imagePaths)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark transfer as delivered: ' . $e->getMessage()
            ], 500);
        }
    }

}
