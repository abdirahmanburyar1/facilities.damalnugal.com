<?php

namespace App\Http\Controllers;

use App\Models\BackOrder;
use App\Models\PackingListDifference;
use App\Http\Resources\BackOrderResource;
use Carbon\Carbon;
use App\Models\BackOrderHistory;
use App\Models\FacilityInventory;
use App\Models\Liquidate;
use App\Models\LiquidateItem;
use App\Models\FacilityInventoryItem;
use Illuminate\Http\Request;

use App\Models\Disposal;
use Illuminate\Support\Facades\DB;
use App\Services\FacilityInventoryMovementService;
use App\Http\Resources\BackOrderHistoryResource;

class BackOrderController extends Controller
{
    public function index(Request $request){
        try {
            $facilityId = auth()->user()->facility_id;
            // Include: order back orders for this facility, transfer back orders for this facility, or any back order whose differences link to this facility
            $backorders = BackOrder::where(function($q) use ($facilityId) {
                $q->whereHas('order', function($oq) use ($facilityId) {
                    $oq->where('facility_id', $facilityId);
                })
                ->orWhereHas('transfer', function($tq) use ($facilityId) {
                    $tq->where('to_facility_id', $facilityId);
                })
                ->orWhereHas('differences.inventoryAllocation', function($query) use ($facilityId) {
                    $query->whereHas('order_item.order', function($orderQuery) use ($facilityId) {
                        $orderQuery->where('facility_id', $facilityId);
                    })->orWhereHas('transfer_item.transfer', function($transferQuery) use ($facilityId) {
                        $transferQuery->where('to_facility_id', $facilityId);
                    });
                });
            });

            if($request->filled('search')){
                $backorders->where(function($query) use ($request) {
                    $query->where('back_order_number', 'like', '%'.$request->search.'%')
                        ->orWhereHas('differences.product', function($productQuery) use ($request) {
                            $productQuery->where('name', 'like', '%'.$request->search.'%')
                                ->orWhere('batch_number', 'like', '%'.$request->search.'%')
                                ->orWhere('barcode', 'like', '%'.$request->search.'%');
                        });
                });
            }

            if($request->filled('status')){
                $backorders->where('status', $request->status);
            }

            $backorders->with([
                'order',
                'transfer',
                'differences.product:id,name,productID',
                'differences.inventoryAllocation.order_item.order:id,order_number,order_type',
                'differences.inventoryAllocation.transfer_item.transfer:id,transferID,transfer_type',
                'creator:id,name',
                'updater:id,name'
            ]);

            $backorders = $backorders->paginate($request->filled('per_page', 25), ['*'], 'page', $request->filled('page', 1))
                ->withQueryString();
            $backorders->setPath(url()->current());

            return inertia('BackOrder/Index', [
                'history' => BackOrderResource::collection($backorders),
                'filters' => $request->only('search', 'per_page', 'page', 'status')
            ]);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            return redirect()->back()->with(['error' => $th->getMessage()]);
        }
    }

    public function manageBackOrder()
    {
        // Non-finalized: NULL, 0, or '' (DB may store 0 for "pending")
        $nonFinalizedDifferences = function ($q) {
            $q->where(function ($q2) {
                $q2->whereNull('finalized')->orWhere('finalized', 0)->orWhere('finalized', '');
            });
        };

        // Get orders with active back orders (non-finalized differences) for this facility
        $orders = \App\Models\Order::where('facility_id', auth()->user()->facility_id)
            ->whereHas('backorders', function ($query) use ($nonFinalizedDifferences) {
                $query->whereHas('differences', $nonFinalizedDifferences);
            })
            ->with(['backorders' => function ($query) use ($nonFinalizedDifferences) {
                $query->whereHas('differences', $nonFinalizedDifferences);
            }])
            ->get()
            ->filter(function ($order) {
                return $order->backorders->count() > 0;
            })
            ->values()
            ->map(function ($order) {
                return [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'order_type' => $order->order_type ?? null,
                    'backOrders' => $order->backorders->map(fn ($bo) => [
                        'id' => $bo->id,
                        'back_order_number' => $bo->back_order_number,
                        'order_id' => $bo->order_id,
                        'transfer_id' => $bo->transfer_id,
                    ])->toArray(),
                ];
            })
            ->toArray();

        // Get transfers with active back orders for this facility
        $transfers = \App\Models\Transfer::where('to_facility_id', auth()->user()->facility_id)
            ->whereHas('backorders', function ($query) use ($nonFinalizedDifferences) {
                $query->whereHas('differences', $nonFinalizedDifferences);
            })
            ->with(['backorders' => function ($query) use ($nonFinalizedDifferences) {
                $query->whereHas('differences', $nonFinalizedDifferences);
            }])
            ->get()
            ->filter(function ($transfer) {
                return $transfer->backorders->count() > 0;
            })
            ->values()
            ->map(function ($transfer) {
                return [
                    'id' => $transfer->id,
                    'transferID' => $transfer->transferID ?? $transfer->transfer_id ?? (string) $transfer->id,
                    'transfer_id' => $transfer->transfer_id ?? $transfer->transferID ?? $transfer->id,
                    'transfer_type' => $transfer->transfer_type ?? null,
                    'backOrders' => $transfer->backorders->map(fn ($bo) => [
                        'id' => $bo->id,
                        'back_order_number' => $bo->back_order_number,
                        'order_id' => $bo->order_id,
                        'transfer_id' => $bo->transfer_id,
                    ])->toArray(),
                ];
            })
            ->toArray();

        return inertia('BackOrder/BackOrder', [
            'orders' => $orders,
            'transfers' => $transfers,
        ]);
    }

    public function getBackOrder(Request $request, $id)
    {
        try {
            $path = $request->path();
            $type = str_contains($path, '/order/') ? 'order' : 'transfer';

            // Non-finalized: NULL, 0, or '' (so items with finalized=0 appear in manage page)
            // Include differences by: (1) allocation chain OR (2) back order source (order_id/transfer_id)
            $query = PackingListDifference::where(function ($q) {
                $q->whereNull('finalized')->orWhere('finalized', 0)->orWhere('finalized', '');
            })
                ->where(function ($q) use ($type, $id) {
                    if ($type === 'order') {
                        $q->whereHas('inventoryAllocation', function ($aq) use ($id) {
                            $aq->whereNotNull('order_item_id')
                                ->whereHas('order_item', function ($oq) use ($id) {
                                    $oq->where('order_id', $id);
                                });
                        })->orWhereHas('backOrder', function ($bq) use ($id) {
                            $bq->where('order_id', $id);
                        });
                    } else {
                        $q->whereHas('inventoryAllocation', function ($aq) use ($id) {
                            $aq->whereNotNull('transfer_item_id')
                                ->whereHas('transfer_item', function ($tq) use ($id) {
                                    $tq->where('transfer_id', $id);
                                });
                        })->orWhereHas('backOrder', function ($bq) use ($id) {
                            $bq->where('transfer_id', $id);
                        });
                    }
                });

            $results = $query->with([
                'product:id,name,productID',
                'inventoryAllocation.order_item.order:id,order_number,order_type',
                'inventoryAllocation.transfer_item.transfer:id,transferID,transfer_type',
                'backOrder:id,back_order_number,back_order_date,status,order_id,transfer_id',
                'backOrder.order:id,order_number,order_type',
                'backOrder.transfer:id,transferID,transfer_type',
            ])->get();

            // Propagate source: from allocation chain or from back order when allocation is missing
            $results = $results->map(function ($item) use ($type, $id) {
                $item->source_id = $id;
                $item->source_type = $type;
                $orderOrTransfer = null;
                if ($type === 'order') {
                    $orderOrTransfer = $item->inventoryAllocation?->order_item?->order
                        ?? $item->backOrder?->order;
                } else {
                    $orderOrTransfer = $item->inventoryAllocation?->transfer_item?->transfer
                        ?? $item->backOrder?->transfer;
                }
                $item->source = $orderOrTransfer ? [
                    'id' => $orderOrTransfer->id,
                    'order_number' => $orderOrTransfer->order_number ?? null,
                    'order_type' => $orderOrTransfer->order_type ?? null,
                    'transferID' => $orderOrTransfer->transferID ?? $orderOrTransfer->transfer_id ?? null,
                    'transfer_id' => $orderOrTransfer->transfer_id ?? $orderOrTransfer->transferID ?? null,
                    'transfer_type' => $orderOrTransfer->transfer_type ?? null,
                ] : null;
                return $item;
            });
            return response()->json($results, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    // liquidate
    public function liquidate(Request $request){
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item (with backOrder for order/transfer propagation)
            $item = PackingListDifference::with([
                'inventoryAllocation.order_item',
                'inventoryAllocation.transfer_item',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }

            $allocation = $item->inventoryAllocation;
            $backOrder = $item->backOrder;
            // Resolve order_id/transfer_id from allocation or back order for propagation
            $orderId = null;
            $transferId = null;
            if ($allocation) {
                if ($allocation->order_item_id && $allocation->order_item) {
                    $orderId = $allocation->order_item->order_id;
                }
                if ($allocation->transfer_item_id && $allocation->transfer_item) {
                    $transferId = $allocation->transfer_item->transfer_id;
                }
            }
            if ($orderId === null && $transferId === null && $backOrder) {
                $orderId = $backOrder->order_id;
                $transferId = $backOrder->transfer_id;
            }

            $facilityName = null;
            if ($orderId) {
                $facilityName = \App\Models\Order::with('facility')->find($orderId)?->facility?->name;
            } elseif ($transferId) {
                $facilityName = \App\Models\Transfer::with('toFacility')->find($transferId)?->toFacility?->name;
            }
            
            $note = $request->note;
            
            // Handle file attachments if any
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $fileName = 'liquidate_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('attachments/liquidations'), $fileName);
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/attachments/liquidations/' . $fileName,
                        'type' => $file->getClientMimeType(),
                        'size' => filesize(public_path('attachments/liquidations/' . $fileName)),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            $liquidate = Liquidate::create([
                'liquidated_by' => auth()->id(),
                'liquidated_at' => Carbon::now(),
                'status' => 'pending',
                'source' => $request->status,
                'back_order_id' => $item->back_order_id,
                'order_id' => $orderId,
                'transfer_id' => $transferId,
                'facility' => $facilityName,
            ]);

            $unitCost = $allocation ? (float) ($allocation->unit_cost ?? 0) : 0;
            $totalCost = $unitCost * $request->quantity;
            LiquidateItem::create([
                'liquidate_id' => $liquidate->id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'unit_cost' => $unitCost,
                'total_cost' => $totalCost,
                'barcode' => $allocation->barcode ?? null,
                'expire_date' => $allocation->expiry_date ?? null,
                'batch_number' => $allocation->batch_number ?? null,
                'uom' => $allocation->uom ?? null,
                'note' => $note,
                'type' => $request->status,
                'attachments' => !empty($attachments) ? $attachments : null,
            ]);

            BackOrderHistory::create([
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Liquidated',
                'note' => $note,
                'performed_by' => auth()->id(),
                'barcode' => $allocation->barcode ?? 'N/A',
                'batch_number' => $allocation->batch_number ?? 'N/A',
                'expiry_date' => $allocation->expiry_date ?? now()->addYears(1)->toDateString(),
                'uom' => $allocation->uom ?? 'N/A',
                'unit_cost' => $allocation->unit_cost ?? 0,
                'total_cost' => ($allocation->unit_cost ?? 0) * $request->quantity,
            ]);
            
            // Update the packing list difference
            // $item->decrement('quantity', $request->quantity);
            // if ($item->quantity <= 0) {
                $item->update([
                    'finalized' => true
                ]);
            // }

            // Update inventory allocation if exists
            // if ($item->inventoryAllocation) {
            //     $item->inventoryAllocation->decrement('allocated_quantity', $request->quantity);
            // }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Liquidated Successfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    // dispose
    public function dispose(Request $request){
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'product_id' => 'required|exists:products,id',
                'quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240', // Max 10MB per file
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item (with backOrder for propagation when allocation is null)
            $item = PackingListDifference::with([
                'inventoryAllocation',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }

            $allocation = $item->inventoryAllocation;
            $note = $request->note;
            
            // Handle file attachments if any
            $attachments = [];
            if ($request->hasFile('attachments')) {
                foreach ($request->file('attachments') as $index => $file) {
                    $fileName = 'dispose_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
                    $file->move(public_path('attachments/disposals'), $fileName);
                    $attachments[] = [
                        'name' => $file->getClientOriginalName(),
                        'path' => '/attachments/disposals/' . $fileName,
                        'type' => $file->getClientMimeType(),
                        'size' => filesize(public_path('attachments/disposals/' . $fileName)),
                        'uploaded_at' => now()->toDateTimeString()
                    ];
                }
            }

            $dispose = Disposal::create([
                'back_order_id' => $item->back_order_id,
                'disposal_by' => auth()->id(),
                'disposed_at' => Carbon::now(),
                'status' => 'pending',
                'source' => $request->status,
            ]);

            BackOrderHistory::create([
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Disposed',
                'note' => $note,
                'performed_by' => auth()->id(),
                'barcode' => $allocation->barcode ?? 'N/A',
                'batch_number' => $allocation->batch_number ?? 'N/A',
                'expiry_date' => $allocation->expiry_date ?? now()->addYears(1)->toDateString(),
                'uom' => $allocation->uom ?? 'N/A',
                'unit_cost' => $allocation->unit_cost ?? 0,
                'total_cost' => ($allocation->unit_cost ?? 0) * $request->quantity,
            ]);
            
            $item->decrement('quantity', $request->quantity);
            if ($item->quantity <= 0) {
                $item->update([
                    'finalized' => true
                ]);
            }

            if ($allocation) {
                $allocation->decrement('allocated_quantity', $request->quantity);
            }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Disposed Successfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    // received
    public function received(Request $request) {
        try {
            // Validate the request
            $validated = $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'back_order_id' => 'required|exists:back_orders,id',
                'product_id' => 'required|exists:products,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'quantity' => 'required|integer|min:1',
                'original_quantity' => 'required|integer|min:1',
                'status' => 'required|string',
            ]);
            
            // Start a database transaction
            DB::beginTransaction();
            
            // Get the packing list difference item
            $item = PackingListDifference::with([
                'inventoryAllocation',
                'product',
                'backOrder'
            ])->find($request->id);
            
            if (!$item) {
                throw new \Exception('Item not found');
            }
            
            // Get inventory allocation details
            $inventoryAllocation = $item->inventoryAllocation;
            $unitCost = $inventoryAllocation && $inventoryAllocation->unit_cost ? (float) $inventoryAllocation->unit_cost : 0.0;
            $totalCost = (float) ($unitCost * $request->quantity);
            
            // Ensure total_cost is never null
            if ($totalCost === null || is_nan($totalCost)) {
                $totalCost = 0.0;
            }
            
            // Determine type based on inventory allocation
            $type = null;
            if ($inventoryAllocation) {
                if ($inventoryAllocation->transfer_item_id !== null) {
                    $type = "Transfer";
                } elseif ($inventoryAllocation->order_item_id !== null) {
                    $type = "Order";
                }
            }
            
            // Create a record in BackOrderHistory with inventory details
            $backOrderHistoryData = [
                'back_order_id' => $item->back_order_id,
                'product_id' => $item->product_id,
                'quantity' => $request->quantity,
                'status' => 'Received',
                'note' => "Received {$request->quantity} items by " . auth()->user()->name,
                'performed_by' => auth()->id(),
                'unit_cost' => $unitCost,
            ];
            
            // Explicitly set total_cost after array creation
            $backOrderHistoryData['total_cost'] = $totalCost;
            
            // Set order_item_id or transfer_item_id based on type
            if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                $backOrderHistoryData['order_item_id'] = $inventoryAllocation->order_item_id;
            } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                $backOrderHistoryData['transfer_item_id'] = $inventoryAllocation->transfer_item_id;
            }            
            
            // Add inventory allocation details if available
            if ($inventoryAllocation) {
                $backOrderHistoryData['batch_number'] = $inventoryAllocation->batch_number ?? 'N/A';
                $backOrderHistoryData['barcode'] = $inventoryAllocation->barcode ?? 'N/A';
                $backOrderHistoryData['expiry_date'] = $inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString();
                $backOrderHistoryData['uom'] = $inventoryAllocation->uom ?? 'N/A';
            } else {
                // Set default values if no inventory allocation
                $backOrderHistoryData['batch_number'] = 'N/A';
                $backOrderHistoryData['barcode'] = 'N/A';
                $backOrderHistoryData['expiry_date'] = now()->addYears(1)->toDateString();
                $backOrderHistoryData['uom'] = 'N/A';
            }
            
            BackOrderHistory::create($backOrderHistoryData);
            
            // Update the packing list difference - deduct the received quantity
            $item->quantity -= $request->quantity;
            if ($item->quantity <= 0) {
                // If quantity becomes zero or negative, delete the difference record
                $item->delete();
            } else {
                // Otherwise, save the updated quantity
                $item->save();
            }
            
            // Update inventory allocation received_quantity if exists
            if ($item->inventoryAllocation) {
                $item->inventoryAllocation->received_quantity += $request->quantity;
                $item->inventoryAllocation->save();
            }
            
            // Commit the transaction
            DB::commit();
            
            return response()->json("Received Successfully.", 200);
        } catch (\Throwable $th) {
            logger()->info($th->getMessage());
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function testBackOrderRoute()
    {
        return response()->json(['message' => 'BackOrder route test successful']);
    }

    public function receiveBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'back_order_id' => 'required|exists:back_orders,id',
                'product_id' => 'required|exists:products,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'quantity' => 'required|integer|min:1',
                'original_quantity' => 'required|integer|min:1',
                'status' => 'required|string',
            ]);

            return DB::transaction(function () use ($request) {
                $packingListDiff = PackingListDifference::with(['inventoryAllocation.order_item', 'inventoryAllocation.transfer_item', 'backOrder'])->find($request->id);
                
                // Calculate remaining quantity
                $receivedQuantity = $request->quantity;
                $originalQuantity = $request->original_quantity;
                $remainingQuantity = $originalQuantity - $receivedQuantity;

                // Get inventory allocation details
                $inventoryAllocation = $packingListDiff ? $packingListDiff->inventoryAllocation : null;
                $unitCost = $inventoryAllocation && $inventoryAllocation->unit_cost ? (float) $inventoryAllocation->unit_cost : 0.0;
                $totalCost = (float) ($unitCost * $receivedQuantity);
                
                // Ensure total_cost is never null
                if ($totalCost === null || is_nan($totalCost)) {
                    $totalCost = 0.0;
                }
                
                // Determine type based on inventory allocation
                $type = null;
                if ($inventoryAllocation) {
                    if ($inventoryAllocation->transfer_item_id !== null) {
                        $type = "Transfer";
                    } elseif ($inventoryAllocation->order_item_id !== null) {
                        $type = "Order";
                    }
                }
                
                // Create BackOrderHistory record with all inventory details
                $backOrderHistoryData = [
                    'packing_list_id' => null,
                    'product_id' => $request->product_id,
                    'quantity' => $receivedQuantity,
                    'status' => 'Received',
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'performed_by' => auth()->user()->id,
                    'back_order_id' => $request->back_order_id,
                    'unit_cost' => $unitCost,
                ];
                
                // Explicitly set total_cost after array creation
                $backOrderHistoryData['total_cost'] = $totalCost;
                
                // Set order_item_id or transfer_item_id based on type from inventory allocation
                if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    $backOrderHistoryData['order_item_id'] = $inventoryAllocation->order_item_id;
                } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    $backOrderHistoryData['transfer_item_id'] = $inventoryAllocation->transfer_item_id;
                }
                
                // Add inventory allocation details if available
                if ($inventoryAllocation) {
                    $backOrderHistoryData['batch_number'] = $inventoryAllocation->batch_number ?? 'N/A';
                    $backOrderHistoryData['barcode'] = $inventoryAllocation->barcode ?? 'N/A';
                    $backOrderHistoryData['expiry_date'] = $inventoryAllocation->expiry_date ?? now()->addYears(1)->toDateString();
                    $backOrderHistoryData['uom'] = $inventoryAllocation->uom ?? 'N/A';
                } else {
                    // Set default values if no inventory allocation
                    $backOrderHistoryData['batch_number'] = 'N/A';
                    $backOrderHistoryData['barcode'] = 'N/A';
                    $backOrderHistoryData['expiry_date'] = now()->addYears(1)->toDateString();
                    $backOrderHistoryData['uom'] = 'N/A';
                }
                
                
                $backOrderHistory = BackOrderHistory::create($backOrderHistoryData);

                // Create ReceivedBackOrder record with inventory allocation details
                $receivedBackOrderData = [
                    'product_id' => $request->product_id,
                    'received_by' => auth()->user()->id,
                    'quantity' => $receivedQuantity,
                    'status' => 'pending',
                    'type' => $type ?? $request->status, // Use type from inventory allocation, fallback to request status
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'received_at' => now()->toDateString(),
                    'back_order_id' => $request->back_order_id,
                ];
                
                // Set order_id or transfer_id from allocation or back order (propagate order/transfer data)
                if ($type === "Order" && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    $orderItem = $inventoryAllocation->order_item ?? \App\Models\OrderItem::find($inventoryAllocation->order_item_id);
                    if ($orderItem) {
                        $receivedBackOrderData['order_id'] = $orderItem->order_id;
                    }
                } elseif ($type === "Transfer" && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    $transferItem = $inventoryAllocation->transfer_item ?? \App\Models\TransferItem::find($inventoryAllocation->transfer_item_id);
                    if ($transferItem) {
                        $receivedBackOrderData['transfer_id'] = $transferItem->transfer_id;
                    }
                }
                // When no allocation (e.g. order-source back order), use back order's order_id/transfer_id
                if (empty($receivedBackOrderData['order_id']) && empty($receivedBackOrderData['transfer_id']) && $packingListDiff->backOrder) {
                    $bo = $packingListDiff->backOrder;
                    if ($bo->order_id) {
                        $receivedBackOrderData['order_id'] = $bo->order_id;
                    }
                    if ($bo->transfer_id) {
                        $receivedBackOrderData['transfer_id'] = $bo->transfer_id;
                    }
                }
                
                // Set facility information from allocation or from back order's order/transfer
                if ($type === 'Order' && $inventoryAllocation && $inventoryAllocation->order_item_id) {
                    $orderItem = $inventoryAllocation->order_item ?? \App\Models\OrderItem::find($inventoryAllocation->order_item_id);
                    if ($orderItem && $orderItem->order && $orderItem->order->facility) {
                        $receivedBackOrderData['facility_id'] = $orderItem->order->facility->id;
                        $receivedBackOrderData['facility'] = $orderItem->order->facility->name;
                    }
                } elseif ($type === 'Transfer' && $inventoryAllocation && $inventoryAllocation->transfer_item_id) {
                    $transferItem = $inventoryAllocation->transfer_item ?? \App\Models\TransferItem::find($inventoryAllocation->transfer_item_id);
                    if ($transferItem && $transferItem->transfer) {
                        $receivedBackOrderData['facility_id'] = $transferItem->transfer->to_facility_id;
                        $toFacility = \App\Models\Facility::find($transferItem->transfer->to_facility_id);
                        if ($toFacility) {
                            $receivedBackOrderData['facility'] = $toFacility->name;
                        }
                    }
                }
                if (empty($receivedBackOrderData['facility_id']) && $packingListDiff->backOrder && $packingListDiff->backOrder->order_id) {
                    $order = \App\Models\Order::with('facility')->find($packingListDiff->backOrder->order_id);
                    if ($order && $order->facility) {
                        $receivedBackOrderData['facility_id'] = $order->facility->id;
                        $receivedBackOrderData['facility'] = $order->facility->name;
                    }
                }
                if (empty($receivedBackOrderData['facility_id']) && $packingListDiff->backOrder && $packingListDiff->backOrder->transfer_id) {
                    $transfer = \App\Models\Transfer::find($packingListDiff->backOrder->transfer_id);
                    if ($transfer) {
                        $receivedBackOrderData['facility_id'] = $transfer->to_facility_id;
                        $toFacility = \App\Models\Facility::find($transfer->to_facility_id);
                        if ($toFacility) {
                            $receivedBackOrderData['facility'] = $toFacility->name;
                        }
                    }
                }
                
                // Add inventory allocation details if available
                if ($packingListDiff && $packingListDiff->inventoryAllocation) {
                    $receivedBackOrderData['barcode'] = $packingListDiff->inventoryAllocation->barcode ?? 'N/A';
                    $receivedBackOrderData['batch_number'] = $packingListDiff->inventoryAllocation->batch_number ?? 'N/A';
                    $receivedBackOrderData['expire_date'] = $packingListDiff->inventoryAllocation->expiry_date ?? null;
                    $receivedBackOrderData['uom'] = $packingListDiff->inventoryAllocation->uom ?? 'N/A';
                    $receivedBackOrderData['unit_cost'] = $packingListDiff->inventoryAllocation->unit_cost ?? 0;
                    $receivedBackOrderData['total_cost'] = ($packingListDiff->inventoryAllocation->unit_cost ?? 0) * $receivedQuantity;
                } else {
                    // Set default values if no inventory allocation
                    $receivedBackOrderData['barcode'] = 'N/A';
                    $receivedBackOrderData['batch_number'] = 'N/A';
                    $receivedBackOrderData['expire_date'] = null;
                    $receivedBackOrderData['uom'] = 'N/A';
                    $receivedBackOrderData['unit_cost'] = 0;
                    $receivedBackOrderData['total_cost'] = 0;
                }
                
                // Generate the received_backorder_number manually to ensure it's set
                $receivedBackOrderData['received_backorder_number'] = \App\Models\ReceivedBackOrder::generateReceivedBackorderNumber();
                
                $receivedBackOrder = \App\Models\ReceivedBackOrder::create($receivedBackOrderData);

                // Create ReceivedBackorderItem record
                $receivedBackorderItemData = [
                    'received_backorder_id' => $receivedBackOrder->id,
                    'product_id' => $request->product_id,
                    'quantity' => $receivedQuantity,
                    'unit_cost' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->unit_cost ?? 0 : 0,
                    'total_cost' => ($packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->unit_cost ?? 0 : 0) * $receivedQuantity,
                    'barcode' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->barcode ?? 'N/A' : 'N/A',
                    'expire_date' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->expiry_date ?? null : null,
                    'batch_number' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->batch_number ?? 'N/A' : 'N/A',
                    'warehouse_id' => null, // Set to null for facilities
                    'uom' => $packingListDiff && $packingListDiff->inventoryAllocation ? $packingListDiff->inventoryAllocation->uom ?? 'N/A' : 'N/A',
                    'location' => null, // Set to null for facilities
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                ];

                \App\Models\ReceivedBackorderItem::create($receivedBackorderItemData);

                // Handle the packing list difference record
                if ($remainingQuantity <= 0) {
                    $packingListDiff->delete();
                } else {
                    $packingListDiff->quantity = $remainingQuantity;
                    $packingListDiff->save();
                }
                
                // Update inventory allocation received_quantity if exists
                if ($packingListDiff && $packingListDiff->inventoryAllocation) {
                    $packingListDiff->inventoryAllocation->received_quantity += $receivedQuantity;
                    $packingListDiff->inventoryAllocation->save();
                }

                return response()->json([
                    'message' => "Successfully received {$receivedQuantity} items" . ($remainingQuantity > 0 ? ", {$remainingQuantity} items remaining" : ""),
                    'received_quantity' => $receivedQuantity,
                    'remaining_quantity' => $remainingQuantity,
                ], 200);

            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function showBackOrder(Request $request)
    {
        $query = BackOrder::query();

        // Only show back orders for the current user's facility
        $query->whereHas('order', function($q) {
            $q->where('facility_id', auth()->user()->facility_id);
        });

        if($request->filled('search')){
            $query->whereHas('order', function($q) use ($request){
                $q->where('order_number', 'like', '%' . $request->search . '%');
            })
            ->orWhere('back_order_number', 'like', '%' . $request->search . '%');
        }
        if($request->filled('status')){
            $query->where('status', $request->status);
        }
        
        // with
        $query = $query->with('order.facility')->latest();
        $history = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $history->setPath(url()->current());

        return inertia('BackOrder/Index', [
            'history' => BackOrderHistoryResource::collection($history),
            'filters' => $request->only('search', 'per_page', 'status')
        ]);
    }

    public function getBackOrderHistories($backOrderId)
    {
        try {
            $histories = BackOrderHistory::with(['product.dosage','product.category', 'performer'])
            ->where('back_order_id', $backOrderId)
            ->get();
        return response()->json($histories, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function uploadBackOrderAttachment(Request $request, $backOrderId)
    {
        try {
            \Log::info('Upload attachment called', [
                'backOrderId' => $backOrderId,
                'files_count' => $request->hasFile('attachments') ? count($request->file('attachments')) : 0,
                'request_data' => $request->all()
            ]);
            
            $request->validate([
                'attachments' => 'required|array',
                'attachments.*' => 'file|mimes:pdf|max:10240', // 10MB max per file
            ]);

            $backOrder = BackOrder::findOrFail($backOrderId);
            \Log::info('BackOrder found', ['backOrder' => $backOrder->toArray()]);
            
            $attachments = [];

            foreach ($request->file('attachments') as $file) {
                $fileName = 'backorder_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();

                // Get file info BEFORE moving
                $mimeType = $file->getMimeType();
                $size = $file->getSize();

                $filePath = public_path('attachments/backorders/' . $fileName);
                $file->move(public_path('attachments/backorders'), $fileName);
                
                \Log::info('File uploaded', [
                    'original_name' => $file->getClientOriginalName(),
                    'stored_name' => $fileName,
                    'file_path' => $filePath,
                    'file_exists' => file_exists($filePath)
                ]);
                
                $attachments[] = [
                    'name' => $file->getClientOriginalName(),
                    'path' => '/attachments/backorders/' . $fileName,
                    'type' => $mimeType,
                    'size' => $size,
                    'uploaded_at' => now()->toDateTimeString()
                ];
            }

            // Merge with existing attachments
            $existingAttachments = $backOrder->attach_documents ?? [];
            $allAttachments = array_merge($existingAttachments, $attachments);
            
            \Log::info('Updating back order attachments', [
                'existing_count' => count($existingAttachments),
                'new_count' => count($attachments),
                'total_count' => count($allAttachments)
            ]);
            
            $backOrder->update(['attach_documents' => $allAttachments]);

            return response()->json(['message' => 'Attachments uploaded successfully', 'files' => $allAttachments], 200);
        } catch (\Throwable $th) {
            \Log::error('Upload attachment error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response()->json($th->getMessage(), 500);
        }
    }

    public function deleteBackOrderAttachment(Request $request, $backOrderId)
    {
        try {
            \Log::info('Delete attachment called', [
                'backOrderId' => $backOrderId,
                'file_path' => $request->file_path,
                'request_data' => $request->all()
            ]);
            
            $request->validate([
                'file_path' => 'required|string'
            ]);

            $backOrder = BackOrder::findOrFail($backOrderId);
            $attachments = $backOrder->attach_documents ?? [];
            
            \Log::info('Current attachments', ['attachments' => $attachments]);

            // Remove the specified attachment
            $attachments = array_filter($attachments, function($attachment) use ($request) {
                return $attachment['path'] !== $request->file_path;
            });

            $backOrder->update(['attach_documents' => array_values($attachments)]);

            // Delete the physical file
            $filePath = public_path($request->file_path);
            \Log::info('Deleting physical file', [
                'file_path' => $filePath,
                'file_exists' => file_exists($filePath)
            ]);
            
            if (file_exists($filePath)) {
                unlink($filePath);
            }

            return response()->json(['message' => 'Attachment deleted successfully', 'files' => array_values($attachments)], 200);
        } catch (\Throwable $th) {
            \Log::error('Delete attachment error', [
                'error' => $th->getMessage(),
                'trace' => $th->getTraceAsString()
            ]);
            return response()->json($th->getMessage(), 500);
        }
    }
}
