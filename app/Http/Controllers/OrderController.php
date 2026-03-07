<?php

namespace App\Http\Controllers;

// App Models
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Facility;
use App\Models\Product;
use App\Models\Warehouse;
use App\Models\Inventory;
use App\Models\InventoryAllocation;
use App\Models\BackOrder;
use App\Models\BackOrderHistory;
use App\Models\PackingListDifference;
use App\Models\ReceivedBackOrder;
use App\Models\Liquidate;
use App\Models\Disposal;
use App\Models\FacilityInventoryItem;
use App\Models\InventoryItem;
use App\Models\FacilityInventory;
use App\Models\Driver;
use App\Models\LogisticCompany;
use App\Models\Transfer;
use App\Models\MonthlyConsumptionItem;
// Note: IssuedQuantity model removed - using FacilityInventoryMovementService instead
// App Events and Resources
use App\Models\EligibleItem;
use App\Events\OrderEvent;
use App\Http\Resources\OrderResource;
use App\Http\Resources\BackOrderHistoryResource;

// Laravel Core
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Carbon\Carbon;

// App Facades
use App\Facades\Kafka;
use App\Services\FacilityInventoryMovementService;

class OrderController extends Controller
{
    /**
     * Reject an entire order
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function rejectOrder(Request $request)
    {
        try {
            DB::beginTransaction();
            
            $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);
            
            $order = Order::findOrFail($request->order_id);
            
            // Update order status to rejected
            $order->status = 'rejected';
            $order->rejected_by = auth()->id();
            $order->rejected_at = now();
            $order->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order has been rejected successfully'
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to reject order: ' . $e->getMessage()
            ], 500);
        }
    }

    public function index(Request $request)
    {
        $facility = $request->facility;
        $facilityLocation = $request->facilityLocation;
        $query = Order::query();

        $query->where('facility_id', auth()->user()->facility_id);

        if($request->filled('search')){
            $query->where('order_number', 'like', "%{$request->search}%");
        }

        if($request->filled('currentStatus')){
            $query->where('status', $request->currentStatus);
        }

        if($request->filled('dateFrom') && !$request->filled('dateTo')){
            $query->whereDate('order_date', $request->dateFrom);
        }

        if($request->filled('dateFrom') && $request->filled('dateTo')){
            $query->whereBetween('order_date', [$request->dateFrom, $request->dateTo]);
        }

        if($request->filled('orderType')){
            $query->where('order_type', $request->orderType);
        }
        
        $query->with(['facility.handledby:id,name', 'user'])
            ->latest();

        $orders = $query->paginate($request->input('per_page', 25), ['*'], 'page', $request->input('page', 1))
            ->withQueryString();
        $orders->setPath(url()->current()); // Force Laravel to use full URLs
        // Get order statistics from orders table
        $stats = DB::table('orders')
            ->where('facility_id', auth()->user()->facility_id)
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->status => $item->count];
            })
            ->toArray();

        // Ensure all statuses have a value
        $defaultStats = [
            'pending' => 0,
            'reviewed' => 0,
            'approved' => 0,
            'rejected' => 0,
            'in_process' => 0,
            'dispatched' => 0,
            'delivered' => 0,
            'received' => 0
        ];

        $stats = array_merge($defaultStats, $stats);
        
        return Inertia::render('Order/Index', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page', 'orderType','currentStatus', 'dateFrom', 'dateTo','per_page'),
            'stats' => $stats,
            'totalOrders' => Order::count()
        ]);
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'order_type' => 'required',
                'order_date' => 'required|date',
                'expected_date' => 'required|date|after_or_equal:order_date',
                'items' => 'required|array',
                'items.*.product_id' => 'required|exists:products,id',
                'items.*.quantity' => 'required|numeric',
                'items.*.amc' => 'required|numeric',
                'items.*.soh' => 'required|numeric',
            ],[
                'items.*.product_id.required' => 'Item is required',
            ]);
            return DB::transaction(function () use ($request) {
                // Generate order number
                $orderNumber = $this->generateOrderNumber();

                $order = Order::create([
                    'order_number' => $orderNumber,
                    'facility_id' => auth()->user()->facility_id,
                    'user_id' => auth()->user()->id,
                    'order_type' => $this->appendQuarterToOrderType($request->order_type),
                    'order_date' => $request->order_date,
                    'expected_date' => $request->expected_date,
                    'note' => $request->notes,
                    'status' => 'pending',
                ]);

                foreach ($request->items as $item) {
                    if($item['product_id'] == null){
                        continue;
                    }
                    // Get available inventory for this product to determine warehouse_id
                    $availableInventory = InventoryItem::where('product_id', $item['product_id'])
                        ->where('quantity', '>', 0)
                        ->where('expiry_date', '>=', Carbon::now()->addMonths(3)->toDateString()) // Only use inventory that doesn't expire in next 3 months
                        ->orderBy('created_at', 'asc') // FIFO principle
                        ->get();

                    // Get warehouse_id from the first available inventory item
                    $warehouseId = $availableInventory->isNotEmpty() ? $availableInventory->first()->warehouse_id : null;

                    // Create order item
                    $orderItem = OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'warehouse_id' => $warehouseId,
                        'quantity_on_order' => $item['quantity_on_order'],
                        'quantity_to_release' => 0, // Initialize to 0, will be updated as inventory is allocated
                        'no_of_days' => $item['no_of_days'],
                        'amc' => $item['amc'],
                        'soh' => $item['soh'],
                        'status' => 'pending',
                    ]);

                    // Calculate the quantity to deduct from inventory (quantity - quantity_on_order)
                    $quantityToDeduct = (float) $item['quantity'] - (float) ($item['quantity_on_order'] ?? 0);

                    $remainingQuantity = $quantityToDeduct; // Only deduct the difference
                    $allocations = [];

                    // Allocate inventory from different batches
                    foreach ($availableInventory as $inventory) {
                        if ($remainingQuantity <= 0) break;

                        $quantityToAllocate = min($remainingQuantity, $inventory->quantity);
                        
                        // Create inventory allocation
                        InventoryAllocation::create([
                            'order_item_id' => $orderItem->id,
                            'product_id' => $item['product_id'],
                            'inventory_id' => $inventory->id,
                            'allocated_quantity' => $quantityToAllocate,
                            'batch_number' => $inventory->batch_number,
                            'barcode' => $inventory->barcode,
                            'warehouse_id' => $inventory->warehouse_id,
                            'location_id' => $inventory->location_id,
                            'expiry_date' => $inventory->expiry_date,
                            'uom' => $inventory->uom,
                            'unit_cost' => $inventory->unit_cost,
                            'total_cost' => $inventory->unit_cost * $quantityToAllocate,
                            'allocation_type' => 'Replenishment'
                        ]);

                        // Update inventory quantity
                        $inventory->quantity -= $quantityToAllocate;
                        $inventory->save();

                        // Update order items quantity to release with actual allocated quantity
                        $orderItem->quantity_to_release += $quantityToAllocate;
                        $orderItem->save();

                        $remainingQuantity -= $quantityToAllocate;
                    }

                    // Check if we couldn't allocate all requested quantity
                    // if ($remainingQuantity > 0) {
                    //     return response()->json("Insufficient inventory for product ID {$item['product_id']}", 500);
                    // }
                }

                return response()->json("Order created successfully", 200);
            });
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function searchProduct(Request $request)
    {
        try {
            $search = $request->input('search');
            $products = Product::where('name', 'like', '%' . $search . '%')
                ->orWhere('barcode', 'like', '%' . $search . '%')
                ->select('id', 'name', 'barcode')
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                    ];
                });

            return response()->json($products, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function receivedQuantity(Request $request){
        try {
            $request->validate([
                'order_item_id' => 'required',
                'received_quantity' => 'required|numeric|min:0',
            ]);
            
            $orderItem = OrderItem::whereHas('order', function($query){
                $query->where('status', 'delivered');
            })->find($request->order_item_id);
            
            if(!$orderItem) {
                return response()->json("Order item not found or order not in delivered stage", 400);
            }
            
            // Validate that received quantity doesn't exceed quantity to release
            if((float) $request->received_quantity > (float) $orderItem->quantity_to_release) {
                return response()->json("Received quantity cannot exceed quantity to release", 400);
            }
            
            $orderItem->received_quantity = $request->received_quantity;
            $orderItem->save();
            
            return response()->json('Received quantity updated successfully', 200);
        } catch (\Throwable $th) {
            logger()->error('Error updating received quantity: ' . $th->getMessage());
            return response()->json($th->getMessage(), 500);
        }
    }

    public function getOutstanding(Request $request, $id)
    {
        try {
            $outstanding = DB::table('order_items')
                ->join('orders', 'orders.id', '=', 'order_items.order_id')
                ->join('facilities', 'facilities.id', '=', 'orders.facility_id')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('order_items.id', $id)
                ->whereNotIn('order_items.status', ['pending', 'delivered'])
                ->select(
                    'products.name as product_name',
                    'facilities.name as facility_name',
                    'order_items.quantity',
                    'order_items.status'
                )
                ->get()
                ->map(function ($item) {
                    return [
                        'product' => $item->product_name,
                        'facility' => $item->facility_name,
                        'quantity' => $item->quantity,
                        'status' => $item->status
                    ];
                });

            return response()->json($outstanding, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function changeItemStatus(Request $request)
    {
        try {
            DB::beginTransaction();

            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'status' => 'required'
            ]);

            $order = Order::with(['items.inventory_allocations.differences', 'backorders'])
                ->where('id', $request->order_id)
                ->first();
                
            if($request->status == 'delivered' && $order->status == 'dispatched'){
                $order->status = 'delivered';
                $order->delivered_at = Carbon::now();
                $order->delivered_by = auth()->user()->id;
                $order->save();
                DB::commit();
                return response()->json("Marked as Delivered", 200);
            }

            // Validation: Check for items that need proper accounting before marking as received
            if($request->status == 'received') {
                $invalidItems = [];
                
                
                foreach ($order->items as $item) {
                    // Calculate the actual quantity that will be processed for this item
                    $totalAllocatedQuantity = 0;
                    $totalFinalQuantity = 0;
                    $hasPackingListDifferences = false;
                    
                    foreach ($item->inventory_allocations as $allocation) {
                        $totalAllocatedQuantity += $allocation->allocated_quantity;
                        $totalBackOrdered = $allocation->differences->sum('quantity');
                        $finalQuantity = $allocation->allocated_quantity - $totalBackOrdered;
                        $totalFinalQuantity += $finalQuantity;
                        
                        if ($allocation->differences->count() > 0) {
                            $hasPackingListDifferences = true;
                        }
                    }
                    
                    // Check if there are any back orders for this order
                    $hasBackOrders = $order->backorders()->exists();
                                        
                    // Check if there's a mismatch between received quantity and final quantity
                    $quantityMismatch = $totalFinalQuantity - (float)$item->received_quantity;
                    
                    // Case 1: Allocated inventory not received at all
                    if ($totalFinalQuantity > 0 && (float)$item->received_quantity == 0 && !$hasPackingListDifferences && !$hasBackOrders) {
                       
                        $invalidItems[] = [
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'quantity_to_release' => $item->quantity_to_release,
                            'received_quantity' => $item->received_quantity,
                            'issue' => 'Allocated inventory not received - needs packing list difference or back order'
                        ];
                    }
                    // Case 2: Quantity mismatch exists but no differences are saved to database
                    elseif ($quantityMismatch > 0 && !$hasPackingListDifferences && !$hasBackOrders) {
                        
                        $invalidItems[] = [
                            'product_name' => $item->product->name ?? 'Unknown Product',
                            'quantity_to_release' => $item->quantity_to_release,
                            'received_quantity' => $item->received_quantity,
                            'issue' => "Quantity mismatch detected: ${quantityMismatch} units need to be accounted for. Please fill out backorder records for the missing items."
                        ];
                    }
                }
                
                if (!empty($invalidItems)) {
                    DB::rollBack();
                    $errorMessage = "⚠️ Some items have not been properly recorded or processed. Please review the Order Items before marking as received.\n\n";                    
                    return response()->json($errorMessage, 500);
                }
            }
            
            $debugInfo = []; // For debugging purposes
            
            $user = auth()->user();
            
            foreach ($order->items as $item) {
                // Debug information for this item
                
                foreach ($item->inventory_allocations as $allocation) {
                    // Calculate total back order quantity for this allocation
                    $totalBackOrdered = $allocation->differences->sum('quantity');
                    if((int) $allocation->allocated_quantity < (int) $totalBackOrdered){
                        DB::rollback();
                        return response()->json('Backorder quantities exceeded the allocated quantity', 500);
                    }
                    $finalQuantity = $allocation->allocated_quantity - $totalBackOrdered;
                    
                    $inventory = FacilityInventory::where('facility_id', $user->facility_id)
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
                            'facility_id' => $order->facility_id,
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
                    if ($finalQuantity > 0) {
                        FacilityInventoryMovementService::recordOrderReceived(
                            $order,
                            $item,
                            $finalQuantity,
                            $allocation->batch_number,
                            $allocation->expiry_date,
                            $allocation->barcode
                        );
                    }
                }
            }
            
            // Update order status to received
            $order->status = 'received';
            $order->received_at = Carbon::now();
            $order->received_by = auth()->user()->id;
            $order->save();

            // Broadcast event if needed
            // Kafka::publishOrderPlaced('Refreshed');
            // event(new OrderEvent('Refreshed'));

            DB::commit();
            
            // Return debug information along with success message
            return response()->json('Order received successfully and inventory updated.', 200);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json($e->getMessage(), 500);
        }
    }

    public function show(Request $request, $id)
    {
        try {
            DB::beginTransaction();
            $order = Order::where('id', $id)
                ->with([
                    'items.product.category',
                    'dispatch.driver', 
                    'items.inventory_allocations.warehouse', 
                    'items.inventory_allocations.location',
                    'items.inventory_allocations.differences', 
                    'items.differences', 
                    'backorders', 
                    'facility', 
                    'user',
                    'reviewedBy', 
                    'approvedBy', 
                    'processedBy',
                    'dispatchedBy',
                    'deliveredBy',
                    'receivedBy'
                ])
                ->first();

            // Get items with SOH using subquery and load relationships
            $items = DB::table('order_items')
                ->select([
                    'order_items.*',
                    'products.name as product_name',
                    'inventory_sums.total_quantity as soh'
                ])
                ->join('products', 'order_items.product_id', '=', 'products.id')
                ->leftJoin(DB::raw('(
                    SELECT product_id, SUM(quantity) as total_quantity
                    FROM inventories
                    GROUP BY product_id
                ) as inventory_sums'), 'products.id', '=', 'inventory_sums.product_id')
                ->where('order_items.order_id', $id)
                ->get();

            // Convert to Eloquent models and load relationships
            $orderItems = collect($items)->map(function ($item) {
                $orderItem = OrderItem::find($item->id);
                if ($orderItem) {
                    $orderItem->product_name = $item->product_name;
                    $orderItem->soh = $item->soh;
                    // Backwards-compatible field for UI expecting `days`
                    $orderItem->days = $orderItem->no_of_days;
                    // Load the relationships
                    $orderItem->load(['differences', 'inventory_allocations.differences']);
                    // Backorders are loaded at the order level
                }
                return $orderItem;
            })->filter();

            $order->items = $orderItems;
            $products = Product::select('id','name')->get();
            
            DB::commit();
            return inertia("Order/Show", ['order' => $order, 'products' => $products]);
        } catch (\Throwable $th) {
            DB::rollBack();
            return inertia("Order/Show", ['error' =>  $th->getMessage()]);
        }
    }

    public function pending(Request $request)
    {
        $query = Order::with(['facility', 'user'])
            ->where('status', 'pending');

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('facility', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10);

        return Inertia::render('Order/Pending', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function approved(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'approved')
            ->latest()
            ->get();

        return Inertia::render('Order/Approved', [
            'orders' => $orders,
        ]);
    }

    public function inProcess(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'in_process')
            ->latest()
            ->get();

        return Inertia::render('Order/InProcess', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function dispatched(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'dispatched')
            ->latest()
            ->get();

        return Inertia::render('Order/Dispatched', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function delivered(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'delivered')
            ->latest()
            ->get();

        return Inertia::render('Order/Delivered', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function received(Request $request)
    {
        $orders = Order::with(['facility', 'user'])
            ->where('status', 'received')
            ->latest()
            ->get();

        return Inertia::render('Order/Received', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function all(Request $request)
    {
        $query = Order::with(['facility', 'user']);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('order_number', 'like', "%{$search}%")
                  ->orWhereHas('facility', function($q) use ($search) {
                      $q->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $orders = $query->latest()->paginate(10);

        return Inertia::render('Order/All', [
            'orders' => OrderResource::collection($orders),
            'filters' => $request->only('search', 'page')
        ]);
    }

    public function create(Request $request){
        $facility = Facility::find(auth()->user()->facility_id);
        $items = Product::whereHas('eligible', function($q) use ($facility) {
            $q->where('facility_type', $facility->facility_type);
        })->get();
        return Inertia::render('Order/Create', [
            'items' => $items
        ]);
    }

    private const QUARTER_START_DATES = [
        1 => '01-09',  // Q1: Sep 1 - Nov 30 (90 days)
        2 => '01-12',  // Q2: Dec 1 - Feb 28/29 (90 days)
        3 => '01-03',  // Q3: Mar 1 - May 31 (90 days)
        4 => '01-06'   // Q4: Jun 1 - Aug 31 (90 days)
    ];

    /**
     * Determine the current quarter based on the current date
     * 
     * @return int
     */
    private function getCurrentQuarter()
    {
        $now = Carbon::now();
        $month = $now->month;
        
        // Q1: Sep (9) - Nov (11)
        if ($month >= 9 && $month <= 11) {
            return 1;
        }
        // Q2: Dec (12) - Feb (2)
        elseif ($month == 12 || $month <= 2) {
            return 2;
        }
        // Q3: Mar (3) - May (5)
        elseif ($month >= 3 && $month <= 5) {
            return 3;
        }
        // Q4: Jun (6) - Aug (8)
        elseif ($month >= 6 && $month <= 8) {
            return 4;
        }
        
        // Fallback (should not reach here)
        return 1;
    }

    /**
     * Append quarter information to order type
     * 
     * @param string $orderType
     * @return string
     */
    private function appendQuarterToOrderType($orderType)
    {
        $quarter = $this->getCurrentQuarter();
        return $orderType . ' Q-' . $quarter;
    }

    private function generateOrderNumber() {
        $now = Carbon::now();
        $quarter = $this->getCurrentQuarter();
        $year = $now->year;

        // Get the last order number for this quarter
        $lastOrder = Order::where('order_number', 'like', "OR-{$quarter}-%")
            ->whereYear('created_at', $year)
            ->orderBy('order_number', 'desc')
            ->first();

        if ($lastOrder) {
            // Extract the sequence number and increment
            $lastSequence = (int) substr($lastOrder->order_number, -4);
            $newSequence = $lastSequence + 1;
        } else {
            $newSequence = 1;
        }

        // Format: OR-1-0001
        return sprintf("OR-%d-%04d", $quarter, $newSequence);
    }

    /**
     * Handle back orders for order items
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function backorder(Request $request)
    {
        try {
            $request->validate([
                'order_item_id' => 'required|exists:order_items,id',
                'differences' => 'required|array',
                'received_quantity' => 'required|numeric|min:0',
                'differences.*.inventory_allocation_id' => 'required|exists:inventory_allocations,id',
                'differences.*.quantity' => 'required|numeric|min:0',
                'differences.*.status' => 'required|in:Missing,Damaged,Expired,Lost',
                'differences.*.notes' => 'nullable|string',
                'differences.*.id' => 'nullable|exists:packing_list_differences,id',
                'deleted_differences' => 'nullable|array',
                'deleted_differences.*' => 'exists:packing_list_differences,id'
            ]);

            return DB::transaction(function () use ($request) {
                
                
                $orderItem = OrderItem::with('order.facility:id,name','inventory_allocations')->find($request->order_item_id);
                $orderItem->received_quantity = $request->received_quantity;
                $orderItem->save();
                
                if ($orderItem->order->facility_id != auth()->user()->facility_id) {
                    return response()->json('You are not authorized to record differences for this order.', 403);
                }
                // Find or create a single BackOrder for the entire order (matching warehouse pattern)
                $hasDifferenceItems = collect($request->differences)
                    ->filter(function($item) { return !empty($item); })
                    ->isNotEmpty();
                $backOrder = null;
                if ($hasDifferenceItems) {
                    $backOrder = BackOrder::firstOrCreate(
                        ['order_id' => $orderItem->order_id],
                        [
                            'order_id' => $orderItem->order_id,
                            'back_order_date' => now()->toDateString(),
                            'created_by' => auth()->user()->id,
                            'source_type' => 'order',
                            'reported_by' => $orderItem->order->facility->name ?? 'Unknown Facility',
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
                        ->where('order_item_id', $orderItem->id)
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
                            'product_id' => $orderItem->product_id,
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

    public function removeBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:packing_list_differences,id'
            ]);
            
            $difference = PackingListDifference::findOrFail($request->id);
            $difference->delete();
            
            // Update the parent BackOrder totals if it exists
            if ($difference->backOrder) {
                $difference->backOrder->updateTotals();
            }
            
            return response()->json('Difference has been removed successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Receive back order items (similar to warehouse pattern)
     */
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
                $packingListDiff = PackingListDifference::with('inventoryAllocation')->find($request->id);
                
                // Calculate remaining quantity
                $receivedQuantity = $request->quantity;
                $originalQuantity = $request->original_quantity;
                $remainingQuantity = $originalQuantity - $receivedQuantity;

                // Get inventory allocation details
                $inventoryAllocation = $packingListDiff ? $packingListDiff->inventoryAllocation : null;
                $unitCost = $inventoryAllocation ? $inventoryAllocation->unit_cost : null;
                $totalCost = $unitCost ? ($unitCost * $receivedQuantity) : null;
                
                // Create BackOrderHistory record with all inventory details
                $backOrderHistoryData = [
                    'packing_list_id' => null,
                    'order_id' => $request->source_type === 'order' ? $request->source_id : null,
                    'transfer_id' => $request->source_type === 'transfer' ? $request->source_id : null,
                    'product_id' => $request->product_id,
                    'quantity' => $receivedQuantity,
                    'status' => 'Received',
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'performed_by' => auth()->user()->id,
                    'back_order_id' => $request->back_order_id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    
                ];
                
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
                    'status' => 'received',
                    'type' => $request->status,
                    'note' => "Received {$receivedQuantity} items by " . auth()->user()->name,
                    'received_at' => now()->toDateString(),
                    'back_order_id' => $request->back_order_id,
                    'order_id' => $request->source_type === 'order' ? $request->source_id : null,
                    'transfer_id' => $request->source_type === 'transfer' ? $request->source_id : null,
                ];
                
                // Set facility information based on source type
                if ($request->source_type === 'order') {
                    // Get order facility details
                    $order = Order::find($request->source_id);
                    if ($order && $order->facility) {
                        $receivedBackOrderData['facility_id'] = $order->facility->id;
                        $receivedBackOrderData['facility'] = $order->facility->name;
                    }
                } elseif ($request->source_type === 'transfer') {
                    // Get transfer facility details
                    $transfer = Transfer::find($request->source_id);
                    if ($transfer) {
                        $receivedBackOrderData['facility_id'] = $transfer->to_facility_id;
                        // Get facility name
                        $toFacility = Facility::find($transfer->to_facility_id);
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
                $receivedBackOrderData['received_backorder_number'] = ReceivedBackOrder::generateReceivedBackorderNumber();
                
                $receivedBackOrder = ReceivedBackOrder::create($receivedBackOrderData);

                // Handle the packing list difference record
                if ($remainingQuantity <= 0) {
                    $packingListDiff->delete();
                } else {
                    $packingListDiff->quantity = $remainingQuantity;
                    $packingListDiff->save();
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

    public function manageBackOrder()
    {
        // Get orders with active back orders (those that have non-finalized differences)
        $orders = Order::where('facility_id', auth()->user()->facility_id)
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
            ->get()
            ->filter(function($order) {
                return $order->backOrders->count() > 0;
            });

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
            ->get(['id', 'transferID', 'transfer_type']) // Use correct DB column name
            ->filter(function($transfer) {
                return $transfer->backOrders->count() > 0;
            });

        return inertia('BackOrder/BackOrder', [
            'orders' => $orders,
            'transfers' => $transfers
        ]);
    }

    public function checkInventory(Request $request)
    {
        try {    
            $request->validate([
                'product_id' => 'required|exists:products,id'
            ]);
            
            $productId = $request->product_id;
            $user = auth()->user();
            $facilityId = $request->input('facility_id', $user->facility_id);
            $facility = Facility::find($facilityId);
            if (!$facility) {
                return response()->json("Facility not found.", 500);
            }
    
            // Check eligibility without facility_id as per your logic
            $isEligible = EligibleItem::where('product_id', $productId)
                ->where('facility_type', $facility->facility_type)
                ->with('product');
    
            if (!$isEligible->exists()) {
                return response()->json("This item is not eligible for ordering.", 500);
            }
    
            // Check if product is in active order pipeline (status != received)
            $inThePipeline = OrderItem::where('product_id', $productId)
                ->whereHas('order', function ($query) use ($facility) {
                    $query->where('facility_id', $facility->id)
                        ->whereNotIn('status', ['received', 'rejected']);
                })
                ->first();
    
            if ($inThePipeline) {
                return response()->json("This item is already in an active order pipeline.", 500);
            }
    
            // Calculate Stock on Hand (SOH)
            $soh = FacilityInventoryItem::whereHas('inventory', function($query) use ($facilityId){
                $query->where('facility_id', $facilityId);
            })
                ->where('product_id', $request->product_id)
                ->sum('quantity');

            // if($soh){
            //     $soh = 0;
            // }
    
            // New AMC Screening Logic - Finding AMC using the specified formula
            // Get monthly consumption data for the item (optimized query)
            $startMonth = $request->input('start_month');
            $endMonth = $request->input('end_month');

            $monthlyConsumptions = DB::table('monthly_consumption_items as mci')
                ->join('monthly_consumption_reports as mcr', 'mci.parent_id', '=', 'mcr.id')
                ->select('mci.id', 'mci.product_id', 'mci.quantity', 'mci.quantity as consumption', 'mcr.month_year', 'mcr.facility_id')
                ->where('mcr.facility_id', $facility->id)
                ->where('mci.product_id', $productId)
                ->where('mci.quantity', '>', 0) // Pre-filter zero quantities at database level
                ->when($startMonth && $endMonth, function($q) use ($startMonth, $endMonth){
                    $q->whereBetween('mcr.month_year', [$startMonth, $endMonth]);
                })
                ->orderBy('mcr.month_year', 'desc') // Database-level sorting (newest first)
                ->limit(12) // Limit to last 12 months for performance
                ->get();

            $totalMonths = [];
            $includedMonths = [];
            $eligibleMonths = [];
            
            // Convert to array for faster processing
            $monthsData = $monthlyConsumptions->toArray();
            
            // Build total months array for output (already filtered for non-zero)
            foreach ($monthsData as $consumption) {
                $totalMonths[] = [
                    'month' => $consumption->month_year,
                    'quantity' => (float) $consumption->quantity
                ];
            }

            // Apply new AMC screening logic - find 3 months that pass screening
            $eligibleMonths = [];
            $processedMonths = [];
            $monthsCount = count($monthsData);
            
            // Determine start index: skip only if the first month equals the actual current month
            $startIndex = 0;
            if ($monthsCount > 0) {
                $currentMonthY = Carbon::now()->format('Y-m');
                if (isset($monthsData[0]->month_year) && $monthsData[0]->month_year === $currentMonthY) {
                    $startIndex = 1; // skip real current month
                }
            }

            // New AMC calculation logic
            $selectedMonths = [];
            $amc = 0;
            
            if ($monthsCount >= 3) {
                // Start with the first 3 months (excluding current month if applicable)
                $firstThreeMonths = array_slice($monthsData, $startIndex, 3);
                
                // Calculate average of first 3 months
                $sum = 0;
                foreach ($firstThreeMonths as $month) {
                    $sum += (float) $month->consumption;
                }
                $average = $sum / 3;
                
                // Check each month against the average (70% threshold)
                $passedMonths = [];
                $failedMonths = [];
                
                foreach ($firstThreeMonths as $month) {
                    $quantity = (float) $month->consumption;
                    $deviation = abs($quantity - $average);
                    $percentage = $average > 0 ? ($deviation / $average) * 100 : 0;
                    
                    if ($percentage <= 70) {
                        $passedMonths[] = $month;
                    } else {
                        $failedMonths[] = $month;
                    }
                }
                
                // If all 3 months passed, use them
                if (count($passedMonths) == 3) {
                    $selectedMonths = $passedMonths;
                    $amc = $average;
                } else {
                    // Need to find more months to get 3 that pass
                    $remainingMonths = array_slice($monthsData, $startIndex + 3);
                    $candidates = array_merge($passedMonths, $remainingMonths);
                    
                    // Try to find 3 months that pass screening together
                    $foundValidGroup = false;
                    
                    for ($i = 0; $i <= count($candidates) - 3 && !$foundValidGroup; $i++) {
                        $testGroup = array_slice($candidates, $i, 3);
                        $testSum = 0;
                        foreach ($testGroup as $month) {
                            $testSum += (float) $month->consumption;
                        }
                        $testAverage = $testSum / 3;
                        
                        // Check if all months in this group pass
                        $allPass = true;
                        foreach ($testGroup as $month) {
                            $quantity = (float) $month->consumption;
                            $deviation = abs($quantity - $testAverage);
                            $percentage = $testAverage > 0 ? ($deviation / $testAverage) * 100 : 0;
                            
                            if ($percentage > 70) {
                                $allPass = false;
                                break;
                            }
                        }
                        
                        if ($allPass) {
                            $selectedMonths = $testGroup;
                            $amc = $testAverage;
                            $foundValidGroup = true;
                            break;
                        }
                    }
                    
                    // If no valid group found, use the months that passed initially
                    if (!$foundValidGroup && count($passedMonths) > 0) {
                        $selectedMonths = $passedMonths;
                        $amc = count($passedMonths) > 1 ? array_sum(array_map(function($m) { return (float) $m->consumption; }, $passedMonths)) / count($passedMonths) : (float) $passedMonths[0]->consumption;
                    }
                }
            } elseif ($monthsCount == 2) {
                // If only 2 months available
                $selectedMonths = array_slice($monthsData, $startIndex, 2);
                $sum = 0;
                foreach ($selectedMonths as $month) {
                    $sum += (float) $month->consumption;
                }
                $amc = $sum / 2;
            } elseif ($monthsCount == 1) {
                // If only 1 month available
                $selectedMonths = array_slice($monthsData, $startIndex, 1);
                $amc = (float) $selectedMonths[0]->consumption;
            }
            
            // Build processed months array for transparency
            foreach ($monthsData as $index => $month) {
                $monthName = $month->month_year;
                $currentQuantity = (float) $month->consumption;
                $isSelected = in_array($month, $selectedMonths);
                
                if ($index == 0 && isset($monthsData[0]->month_year) && $monthsData[0]->month_year === Carbon::now()->format('Y-m')) {
                    $processedMonths[] = [
                        'month' => $monthName,
                        'quantity' => $currentQuantity,
                        'status' => 'excluded',
                        'reason' => 'Current month - excluded from AMC calculation'
                    ];
                } else {
                    $processedMonths[] = [
                        'month' => $monthName,
                        'quantity' => $currentQuantity,
                        'status' => $isSelected ? 'included' : 'excluded',
                        'reason' => $isSelected ? 'Passed AMC screening (≤70% deviation)' : 'Failed AMC screening (>70% deviation)'
                    ];
                }
            }
            
            $includedMonths = $processedMonths;
            $eligibleCount = count($selectedMonths);    
            // Determine days since last received order update, fallback to quarter start if none
            $lastReceivedOrder = Order::where('facility_id', $facility->id)
                ->where('status', 'received')
                ->whereHas('items', function ($q) use ($productId) {
                    $q->where('product_id', $productId);
                })
                ->where('order_type', 'quarterly')
                ->orderBy('updated_at', 'desc')
                ->first();

            $now = Carbon::now();
            $quarter = $this->getCurrentQuarter();
            $quarterStartDateParts = explode('-', self::QUARTER_START_DATES[$quarter]);
            $quarterStart = Carbon::createFromDate($now->year, $quarterStartDateParts[1], $quarterStartDateParts[0])->startOfDay();
            
            if ($lastReceivedOrder) {
                $lastReceivedDate = Carbon::parse($lastReceivedOrder->updated_at)->startOfDay();
                $daysSince = $lastReceivedDate->diffInDays($now->startOfDay());
            } else {
                // Fallback: use quarter start date
                $daysSince = $quarterStart->diffInDays($now->startOfDay());
            }
    
            // Days remaining in 90-day quarter cycle
            // Formula: 90 - days_since_quarter_start
            $daysRemaining = 90 - $daysSince;
    
            // // Quantity on Order (QOO) default to 0
            $qoo = 0;
    
            // Calculate projected consumption:
            // AMC is average monthly, so convert to daily by dividing by ~30 days
            $projectedConsumption = ($amc / 30) * $daysRemaining;
    
            // Calculate required quantity = projected consumption - SOH - QOO
            $requiredQuantity = ceil($projectedConsumption - $soh - $qoo);
            $requiredQuantity = max(0, $requiredQuantity);
    
            // If no AMC and no SOH and quantity zero, assign default order quantity (first time order)
            if ($amc == 0 && $soh == 0 && $requiredQuantity == 0) {
                $requiredQuantity = (int) $daysRemaining; // default value for first order, adjust as needed
            }
    
            $product = $isEligible->first()->product;
    
            // Check total available inventory (with expiry check)
            $totalInventory = Inventory::where('product_id', $product->id)
                ->whereHas('items', function($q){
                    $q->where('expiry_date', '>=', Carbon::now()->addMonths(1)->toDateString());
                })->withSum('items', 'quantity')
                ->first();
       
    
            return response()->json([
                'name' => $product->name,
                'quantity' => $requiredQuantity ?? 0,
                'soh' => $soh,
                'amc' => round($amc, 2),
                'days_since_quarter_start' => $daysRemaining,
                'no_of_days' => $daysRemaining,
                'insufficient_inventory' => false,
                // AMC Screening Results
                'included_months' => $includedMonths,
                'total_months' => $totalMonths,
                'eligible_months_count' => $eligibleCount,
                'selected_months' => array_map(function($month) {
                    return [
                        'month' => $month->month_year,
                        'quantity' => (float) $month->consumption
                    ];
                }, $selectedMonths)
            ], 200);
    
        } catch (\Throwable $e) {
            logger()->error('Error in checkInventory', ['message' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json($e->getMessage(), 500);
        }
    }
    
    /**
     * Add dispatch information to an order
     */
    public function dispatchInfo(Request $request){
        try {
            return DB::transaction(function() use ($request){
                $request->validate([
                    'dispatch_date' => 'required|date',
                    'driver_id' => 'required|exists:drivers,id',
                    'driver_number' => 'required|string',
                    'plate_number' => 'required|string',
                    'no_of_cartoons' => 'required|numeric',
                    'order_id' => 'required|exists:orders,id',
                    'logistic_company_id' => 'required|exists:logistic_companies,id',
                    'status' => 'required|string'
                ]);

                $order = Order::with('dispatch')->find($request->order_id);
                $order->dispatch()->create([
                    'order_id' => $request->order_id,
                    'dispatch_date' => $request->dispatch_date,
                    'driver_id' => $request->driver_id,
                    'logistic_company_id' => $request->logistic_company_id,
                    'driver_number' => $request->driver_number,
                    'plate_number' => $request->plate_number,
                    'no_of_cartoons' => $request->no_of_cartoons,
                ]);

                $order->status = $request->status;
                $order->dispatched_at = now();
                $order->dispatched_by = auth()->user()->id;
                $order->save();
                
                return response()->json("Dispatched Successfully", 200);
            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }
    
    /**
     * Delete an order
     */
    public function destroy(Order $order)
    {
        try {
            if ($order->status !== 'pending') {
                return back()->with('error', 'Only pending orders can be deleted.');
            }

            $order->items()->delete();
            $order->delete();

            return back()->with('success', 'Order deleted successfully.');
        } catch (\Throwable $th) {
            return back()->with($th->getMessage(), 500);
        }
    }

    /**
     * Bulk delete orders
     */
    public function bulk(Request $request)
    {
        try {
            $orderIds = $request->input('orderIds');

            // Validate that at least one order is selected
            if (empty($orderIds)) {
                return response()->json('Please select at least one order', 400);
            }

            // Get all selected orders
            $orders = Order::whereIn('id', $orderIds)->get();

            // Check if any order has non-pending items and collect their IDs
            $nonPendingOrders = [];
            foreach ($orders as $order) {
                if ($order->status !== 'pending') {
                    $nonPendingOrders[] = $order->id;
                }
            }

            if (!empty($nonPendingOrders)) {
                return response()->json('Cannot delete orders that are not in pending status', 500);
            }

            // Delete orders if all are pending
            $orders->each(function ($order) {
                $order->items()->delete();
                $order->delete();
            });

            return response()->json('Selected orders deleted successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Update order item quantity
     */
    public function updateQuantity(Request $request)
    {
        try {
            DB::beginTransaction();
    
            $request->validate([
                'item_id'  => 'required|exists:order_items,id',
                'quantity' => 'required|numeric',
                'days'     => 'required|numeric',
                'type'     => 'required|in:quantity_to_release,days',
            ]);
    

            $orderItem = OrderItem::find($request->item_id);
            $order = $orderItem->order;
            

            if($orderItem->quantity <= 0) {
                $orderItem->quantity = $request->quantity;
                $orderItem->save();
                $orderItem->refresh();
            }
    
            if (!in_array($order->status, ['pending'])) {
                return response()->json('Cannot update quantity for orders that are not in pending status', 500);
            }
    
            // Handle original quantity fallback
            $originalQuantity = $orderItem->quantity > 0 ? $orderItem->quantity : $request->quantity;
            $originalDays = $orderItem->no_of_days > 0 ? $orderItem->no_of_days : 1;
            $dailyUsageRate = $originalQuantity / $originalDays;
            
    
            // Calculate new quantity and days
            if ($request->type === 'days') {
                $newDays = (int) ceil($request->days);
                $newQuantityToRelease = (int) ceil($dailyUsageRate * $newDays);
                $orderItem->no_of_days = $newDays;
            } else {
                $newQuantityToRelease = (int) ceil($request->quantity);
                $newDays = (int) ceil($dailyUsageRate > 0 ? ($newQuantityToRelease / $dailyUsageRate) : 1);
                $orderItem->no_of_days = $newDays;
            }
    
            $oldQuantityToRelease = $orderItem->quantity_to_release ?? 0;
            
            // Case 1: Decrease
            if ($newQuantityToRelease < $oldQuantityToRelease) {
                $quantityToRemove = $oldQuantityToRelease - $newQuantityToRelease;
                $remainingToRemove = $quantityToRemove;
    
                $allocations = $orderItem->inventory_allocations()->orderBy('expiry_date', 'desc')->get();
    
                foreach ($allocations as $allocation) {
                    if ($remainingToRemove <= 0) break;
    
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
                            'location'     => $allocation->location,
                            'batch_number' => $allocation->batch_number,
                            'uom'          => $allocation->uom,
                            'barcode'      => $allocation->barcode,
                            'expiry_date'  => $allocation->expiry_date,
                            'quantity'     => $restoreQty
                        ]);
                    }
    
                    if ($allocation->allocated_quantity <= $remainingToRemove) {
                        $remainingToRemove -= $allocation->allocated_quantity;
                        $allocation->delete();
                    } else {
                        $allocation->allocated_quantity -= $remainingToRemove;
                        $allocation->save();
                        $remainingToRemove = 0;
                    }
                }
    
                $orderItem->quantity_to_release = $newQuantityToRelease;
                $orderItem->save();
    
                DB::commit();
                return response()->json('Quantity to release decreased successfully', 200);
            }
    
            // Case 2: Increase
            if ($newQuantityToRelease > $oldQuantityToRelease) {
                $quantityToAdd = $newQuantityToRelease - $oldQuantityToRelease;
                $remainingToAllocate = $quantityToAdd;
                
                $inventoryItems = InventoryItem::where('product_id', $orderItem->product_id)
                    ->where('quantity', '>', 0)
                    ->orderBy('expiry_date', 'asc')
                    ->get();
                
                foreach ($inventoryItems as $inventory) {
                    if ($remainingToAllocate <= 0) break;
                    
                    $quantityToAllocate = min($remainingToAllocate, $inventory->quantity);
                    
                    // Create inventory allocation
                    InventoryAllocation::create([
                        'order_item_id' => $orderItem->id,
                        'product_id' => $orderItem->product_id,
                        'inventory_id' => $inventory->id,
                        'allocated_quantity' => $quantityToAllocate,
                        'batch_number' => $inventory->batch_number,
                        'barcode' => $inventory->barcode,
                        'warehouse_id' => $inventory->warehouse_id,
                        'location_id' => $inventory->location_id,
                        'expiry_date' => $inventory->expiry_date,
                        'uom' => $inventory->uom,
                        'unit_cost' => $inventory->unit_cost,
                        'total_cost' => $inventory->unit_cost * $quantityToAllocate,
                        'allocation_type' => 'Replenishment'
                    ]);
                    
                    // Update inventory quantity
                    $inventory->quantity -= $quantityToAllocate;
                    $inventory->save();
                    
                    $remainingToAllocate -= $quantityToAllocate;
                }
                
                $orderItem->quantity_to_release = $newQuantityToRelease;
                $orderItem->save();
                
                DB::commit();
                return response()->json('Quantity to release increased successfully', 200);
            }
            
            DB::commit();
            return response()->json('Quantity updated successfully', 200);
            
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Update individual order item
     */
    public function updateItem(Request $request)
    {
        try {
            $request->validate([
                'item_id' => 'required|exists:order_items,id',
                'quantity' => 'required|numeric|min:1',
            ]);

            $orderItem = OrderItem::findOrFail($request->item_id);
            $order = $orderItem->order;

            if ($order->status !== 'pending') {
                return response()->json('Cannot update items for orders that are not in pending status', 500);
            }

            $orderItem->quantity = $request->quantity;
            $orderItem->save();

            return response()->json('Order item updated successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Bulk change order status
     */
    public function bulkChangeStatus(Request $request)
    {
        try {
            $request->validate([
                'orderIds' => 'required|array',
                'status' => 'required|string',
            ]);

            $orders = Order::whereIn('id', $request->orderIds)->get();

            foreach ($orders as $order) {
                $order->status = $request->status;
                
                switch ($request->status) {
                    case 'reviewed':
                        $order->reviewed_by = auth()->id();
                        $order->reviewed_at = now();
                        break;
                    case 'approved':
                        $order->approved_by = auth()->id();
                        $order->approved_at = now();
                        break;
                    case 'rejected':
                        $order->rejected_by = auth()->id();
                        $order->rejected_at = now();
                        break;
                    case 'in_process':
                        $order->processed_by = auth()->id();
                        $order->processed_at = now();
                        break;
                    case 'dispatched':
                        $order->dispatched_by = auth()->id();
                        $order->dispatched_at = now();
                        break;
                    case 'delivered':
                        $order->delivered_by = auth()->id();
                        $order->delivered_at = now();
                        break;
                    case 'received':
                        $order->received_by = auth()->id();
                        $order->received_at = now();
                        break;
                }
                
                $order->save();
            }

            return response()->json('Orders status updated successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Bulk change order item status
     */
    public function bulkChangeItemStatus(Request $request)
    {
        try {
            $request->validate([
                'itemIds' => 'required|array',
                'status' => 'required|string',
            ]);

            $orderItems = OrderItem::whereIn('id', $request->itemIds)->get();

            foreach ($orderItems as $item) {
                $item->status = $request->status;
                $item->save();
            }

            return response()->json('Order items status updated successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    /**
     * Restore a deleted order
     */
    public function restoreOrder(Request $request)
    {
        try {
            $request->validate([
                'order_id' => 'required|exists:orders,id',
            ]);

            $order = Order::withTrashed()->findOrFail($request->order_id);
            $order->restore();

            // Restore associated order items
            $order->items()->withTrashed()->restore();

            return response()->json('Order restored successfully', 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function liquidateBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'product_id' => 'required|exists:products,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'quantity' => 'required|integer|min:1',
                'original_quantity' => 'required|integer|min:1',
                'status' => 'required|string',
                'note' => 'nullable|string|max:255',
                'type' => 'nullable|string',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
                'back_order_id' => 'required|exists:back_orders,id',
            ]);

            return DB::transaction(function () use ($request) {
                $packingListDiff = PackingListDifference::with('inventoryAllocation')->findOrFail($request->id);
                
                // Calculate remaining quantity
                $liquidatedQuantity = $request->quantity;
                $originalQuantity = $request->original_quantity;
                $remainingQuantity = $originalQuantity - $liquidatedQuantity;

                // Handle file attachments
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

                // Get inventory allocation details for cost and other fields
                $inventoryAllocation = $packingListDiff->inventoryAllocation;
                $unitCost = $inventoryAllocation ? ($inventoryAllocation->unit_cost ?? 0) : 0;
                $totalCost = $unitCost * $liquidatedQuantity;
                
                // Create BackOrderHistory record
                $backOrderHistoryData = [
                    'packing_list_id' => null,
                    'order_id' => $request->source_type === 'order' ? $request->source_id : null,
                    'transfer_id' => $request->source_type === 'transfer' ? $request->source_id : null,
                    'product_id' => $request->product_id,
                    'quantity' => $liquidatedQuantity,
                    'status' => 'Liquidated',
                    'note' => $request->note ?? 'Liquidated by ' . auth()->user()->name,
                    'performed_by' => auth()->user()->id,
                    'back_order_id' => $request->back_order_id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'attach_documents' => !empty($attachments) ? $attachments : null,
                ];
                
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

                // Create a new liquidation record (similar to warehouse pattern)
                $liquidate = Liquidate::create([
                    'product_id' => $request->product_id,
                    'liquidated_by' => auth()->id(),
                    'liquidated_at' => Carbon::now(),
                    'quantity' => $liquidatedQuantity,
                    'status' => 'pending', // Default status is pending
                    'note' => $request->note ?? 'Liquidated by ' . auth()->user()->name,
                    'type' => $request->type,
                    'barcode' => $inventoryAllocation ? ($inventoryAllocation->barcode ?? 'N/A') : 'N/A',
                    'expire_date' => $inventoryAllocation ? ($inventoryAllocation->expiry_date ?? null) : null,
                    'batch_number' => $inventoryAllocation ? ($inventoryAllocation->batch_number ?? 'N/A') : 'N/A',
                    'uom' => $inventoryAllocation ? ($inventoryAllocation->uom ?? 'N/A') : 'N/A',
                    'unit_cost' => $unitCost,
                    'tota_cost' => $totalCost, // Note: warehouse has typo 'tota_cost' instead of 'total_cost'
                    'attachments' => !empty($attachments) ? json_encode($attachments) : null,
                    'back_order_id' => $request->back_order_id,
                ]);

                // Handle the packing list difference record
                if ($remainingQuantity <= 0) {
                    $packingListDiff->delete();
                } else {
                    $packingListDiff->quantity = $remainingQuantity;
                    $packingListDiff->save();
                }

                return response()->json([
                    'message' => "Successfully liquidated {$liquidatedQuantity} items" . ($remainingQuantity > 0 ? ", {$remainingQuantity} items remaining" : ""),
                    'liquidated_quantity' => $liquidatedQuantity,
                    'remaining_quantity' => $remainingQuantity,
                    'liquidate' => $liquidate,
                ], 200);

            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function disposeBackOrder(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:packing_list_differences,id',
                'source_id' => 'required',
                'source_type' => 'required|in:order,transfer',
                'note' => 'nullable|string',
                'type' => 'nullable|string',
                'quantity' => 'required|min:1',
                'attachments' => 'nullable|array',
                'attachments.*' => 'nullable|file|mimes:pdf,jpg,jpeg,png,doc,docx|max:10240',
                'back_order_id' => 'nullable|exists:back_orders,id',
            ]);

            return DB::transaction(function () use ($request) {
                $packingListDiff = PackingListDifference::with('inventoryAllocation')->findOrFail($request->id);

                // Handle file attachments
                $attachments = [];
                if ($request->hasFile('attachments')) {
                    foreach ($request->file('attachments') as $index => $file) {
                        $fileName = 'disposal_' . time() . '_' . $index . '.' . $file->getClientOriginalExtension();
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

                // Get inventory allocation details for cost and other fields
                $inventoryAllocation = $packingListDiff->inventoryAllocation;
                $unitCost = $inventoryAllocation ? ($inventoryAllocation->unit_cost ?? 0) : 0;
                $totalCost = $unitCost * $request->quantity;
                
                // Create BackOrderHistory record
                $backOrderHistoryData = [
                    'packing_list_id' => null,
                    'order_id' => $request->source_type === 'order' ? $request->source_id : null,
                    'transfer_id' => $request->source_type === 'transfer' ? $request->source_id : null,
                    'product_id' => $packingListDiff->product_id,
                    'quantity' => $request->quantity,
                    'status' => 'Disposed',
                    'note' => $request->note ?? 'Disposed by ' . auth()->user()->name,
                    'performed_by' => auth()->user()->id,
                    'back_order_id' => $request->back_order_id,
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'attach_documents' => !empty($attachments) ? $attachments : null,
                ];
                
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

                // Create a new disposal record (similar to warehouse pattern)
                $disposal = Disposal::create([
                    'product_id' => $packingListDiff->product_id,
                    'disposed_by' => auth()->id(),
                    'disposed_at' => Carbon::now(),
                    'quantity' => $request->quantity,
                    'status' => 'pending', // Default status is pending
                    'note' => $request->note ?? 'Disposed by ' . auth()->user()->name,
                    'type' => $request->type,
                    'barcode' => $inventoryAllocation ? ($inventoryAllocation->barcode ?? 'N/A') : 'N/A',
                    'expire_date' => $inventoryAllocation ? ($inventoryAllocation->expiry_date ?? null) : null,
                    'batch_number' => $inventoryAllocation ? ($inventoryAllocation->batch_number ?? 'N/A') : 'N/A',
                    'uom' => $inventoryAllocation ? ($inventoryAllocation->uom ?? 'N/A') : 'N/A',
                    'unit_cost' => $unitCost,
                    'total_cost' => $totalCost,
                    'attachments' => !empty($attachments) ? json_encode($attachments) : null,
                    'back_order_id' => $request->back_order_id,
                ]);

                // Delete the packing list difference record
                $packingListDiff->delete();

                return response()->json([
                    'message' => "Successfully disposed {$request->quantity} items",
                    'disposal' => $disposal,
                ], 200);

            });
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function uploadBackOrderAttachment(Request $request, $backOrderId)
    {
        $request->validate([
            'attachments' => 'required|array',
            'attachments.*' => 'file|mimes:pdf|max:10240', // 10MB max per file
        ]);

        $backOrder = BackOrder::findOrFail($backOrderId);
        $existing = $backOrder->attach_documents ?? [];
        $newFiles = [];
        
        foreach ($request->file('attachments') as $file) {
            $fileName = 'backorder_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $file->move(public_path('attachments/backorders'), $fileName);
            $newFiles[] = [
                'name' => $file->getClientOriginalName(),
                'path' => '/attachments/backorders/' . $fileName,
                'type' => $file->getClientMimeType(),
                'size' => filesize(public_path('attachments/backorders/' . $fileName)),
                'uploaded_at' => now()->toDateTimeString()
            ];
        }
        
        $backOrder->attach_documents = array_merge($existing, $newFiles);
        $backOrder->save();
        
        return response()->json([
            'message' => 'Attachments uploaded successfully', 
            'files' => $backOrder->attach_documents
        ]);
    }

    public function deleteBackOrderAttachment(Request $request, $backOrderId)
    {
        $request->validate(['file_path' => 'required|string']);
        $backOrder = BackOrder::findOrFail($backOrderId);
        $files = $backOrder->attach_documents ?? [];
        $files = array_filter($files, function($file) use ($request) {
            if ($file['path'] === $request->file_path) {
                $fullPath = public_path($file['path']);
                if (file_exists($fullPath)) {
                    @unlink($fullPath);
                }
                return false;
            }
            return true;
        });
        $backOrder->attach_documents = array_values($files);
        $backOrder->save();
        
        return response()->json([
            'message' => 'Attachment deleted successfully', 
            'files' => $backOrder->attach_documents
        ]);
    }
}
