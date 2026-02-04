<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Facility;
use Illuminate\Support\Facades\DB;
use App\Models\Product;
use App\Models\Transfer;

use App\Models\Order;
use App\Models\InventoryReport;
use App\Models\EligibleItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        // Get current user's facility_id
        $user = auth()->user();
        $facilityId = $user->facility_id;

        // Get distinct facility types and their counts (only main types)
        $facilityTypes = Facility::select('facility_type', DB::raw('count(*) as count'))
            ->whereIn('facility_type', ['Health Centre', 'Primary Health Unit', 'Regional Hospital', 'District Hospital'])
            ->groupBy('facility_type')
            ->orderBy('count', 'desc')
            ->get()
            ->map(function ($type) {
                return [
                    'label' => $this->getAbbreviatedName($type->facility_type),
                    'fullName' => $type->facility_type,
                    'value' => $type->count,
                    'color' => $this->getFacilityTypeColor($type->facility_type),
                ];
            });
        


        // Get current user's facility type
        $facility = \App\Models\Facility::find($facilityId);
        $facilityType = $facility ? $facility->facility_type : null;

        // Product category counts - dynamically fetch from Category model
        $productCategoryCounts = [];
        
        // Get all active categories
        $categories = \App\Models\Category::where('is_active', true)->get();
        
        foreach ($categories as $category) {
            $count = EligibleItem::whereHas('product.category', function($q) use ($category) { 
                    $q->where('name', $category->name); 
                })
                ->where('facility_type', $facilityType)
                ->count();
                
            $productCategoryCounts[$category->name] = $count;
        }

        // Transfer received count - filtered by current user's facility
        $transferReceivedCount = Transfer::where(function($query) use ($facilityId) {
                $query->where('from_facility_id', $facilityId)
                    ->orWhere('to_facility_id', $facilityId);
            })
            ->where('status', 'received')
            ->count();

        // Order status statistics - filtered by current user's facility
        $orderStats = [
            'pending' => Order::where('facility_id', $facilityId)->where('status', 'pending')->count(),
            'reviewed' => Order::where('facility_id', $facilityId)->where('status', 'reviewed')->count(),
            'approved' => Order::where('facility_id', $facilityId)->where('status', 'approved')->count(),
            'in_process' => Order::where('facility_id', $facilityId)->where('status', 'in_process')->count(),
            'dispatched' => Order::where('facility_id', $facilityId)->where('status', 'dispatched')->count(),
            'delivered' => Order::where('facility_id', $facilityId)->where('status', 'delivered')->count(),
            'received' => Order::where('facility_id', $facilityId)->where('status', 'received')->count(),
            'rejected' => Order::where('facility_id', $facilityId)->where('status', 'rejected')->count(),
        ];



        // Delayed orders count - filtered by current user's facility
        $ordersDelayedCount = Order::where('facility_id', $facilityId)
            ->whereNotNull('order_date')
            ->whereNotNull('expected_date')
            ->whereRaw('order_date < expected_date')
            ->count();

        // Inventory statistics
        $statusCounts = [
            'in_stock' => 0,
            'low_stock' => 0,
            'out_of_stock' => 0,
        ];
        
        // For facilities, we'll use facility inventory filtered by current user's facility
        $facilityInventories = \App\Models\FacilityInventory::where('facility_id', $facilityId)->with('items')->get();
        foreach ($facilityInventories as $inventory) {
            $reorderLevel = $inventory->reorder_level ?? 0;
            foreach ($inventory->items ?? [] as $item) {
                $qty = $item->quantity;
                if ($qty == 0) {
                    $statusCounts['out_of_stock']++;
                } elseif ($qty <= $reorderLevel) {
                    $statusCounts['low_stock']++;
                } else {
                    $statusCounts['in_stock']++;
                }
            }
        }

        // Expired statistics
        $now = Carbon::now();
        $sixMonthsFromNow = $now->copy()->addMonths(6);
        $oneYearFromNow = $now->copy()->addYear();

        $expiredCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '<', $now)
            ->count();
        $expiring6MonthsCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '>=', $now)
            ->where('facility_inventory_items.expiry_date', '<=', $sixMonthsFromNow)
            ->count();
        $expiring1YearCount = \App\Models\FacilityInventoryItem::join('facility_inventories', 'facility_inventory_items.facility_inventory_id', '=', 'facility_inventories.id')
            ->where('facility_inventories.facility_id', $facilityId)
            ->where('facility_inventory_items.quantity', '>', 0)
            ->where('facility_inventory_items.expiry_date', '>=', $now)
            ->where('facility_inventory_items.expiry_date', '<=', $oneYearFromNow)
            ->count();

        $expiredStats = [
            'expired' => $expiredCount,
            'expiring_within_6_months' => $expiring6MonthsCount,
            'expiring_within_1_year' => $expiring1YearCount,
        ];

        $responseData = [
            'dashboardData' => [
                'summary' => $facilityTypes,
                'order_stats' => [],
                'tasks' => [],
                'recommended_actions' => [],
                'product_status' => []
            ],
            'productCategoryCard' => $productCategoryCounts,
            'transferReceivedCard' => $transferReceivedCount,
            'orderStats' => $orderStats,
            'ordersDelayedCount' => $ordersDelayedCount,
            'inventoryStatusCounts' => collect($statusCounts)->map(fn($count, $status) => ['status' => $status, 'count' => $count])->values(),
            'expiredStats' => $expiredStats,
        ];

        return Inertia::render('Dashboard', $responseData);
    }

    /**
     * Get human-readable type label
     */
    private function getTypeLabel($type)
    {
        $labels = [
            'beginning_balance' => 'Beginning Balance',
            'received_quantity' => 'Quantity Received',
            'issued_quantity' => 'Quantity Issued',
            'closing_balance' => 'Closing Balance'
        ];

        return $labels[$type] ?? 'Unknown';
    }

    private function getFacilityTypeColor($facilityType)
    {
        $colors = [
            'Regional Hospital' => 'red',
            'District Hospital' => 'orange',
            'Health Centre' => 'blue',
            'Primary Health Unit' => 'green'
        ];

        return $colors[$facilityType] ?? 'gray';
    }

    private function getAbbreviatedName($facilityType)
    {
        $abbreviations = [
            'Regional Hospital' => 'RH',
            'District Hospital' => 'DH',
            'Health Centre' => 'HC',
            'Primary Health Unit' => 'PHU'
        ];

        return $abbreviations[$facilityType] ?? $facilityType;
    }

    /**
     * Get facility tracert items data for analytics
     */
    public function facilityTracertItems(Request $request)
    {
        try {
            // Get current user's facility_id (no facility filter needed)
            $user = auth()->user();
            $facilityId = $user->facility_id;

            if (!$facilityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not assigned to any facility'
                ]);
            }

            // Validate and set defaults
            $type = $request->input('type', 'opening_balance');
            $month = $request->input('month');

            // If no month specified, use previous month
            if (!$month) {
                $month = Carbon::now()->subMonth()->format('Y-m');
            }

            // Validate the type is one of the allowed columns
            $allowedTypes = ['opening_balance', 'stock_received', 'stock_issued', 'closing_balance', 'positive_adjustments', 'negative_adjustments'];
            if (!in_array($type, $allowedTypes)) {
                $type = 'opening_balance';
            }

            Log::info('Facility Tracert Items Request:', [
                'facility_id' => $facilityId,
                'type' => $type,
                'month' => $month
            ]);

            // Build query for facility monthly reports using Eloquent (like warehouse implementation)
            $facilityReport = \App\Models\FacilityMonthlyReport::where('report_period', $month)
                ->where('facility_id', $facilityId)
                ->with(['facility', 'items.product.category'])
                ->first();

            if (!$facilityReport || empty($facilityReport->items)) {
                return response()->json([
                    'success' => false,
                    'message' => "No inventory data found for the selected period ({$month})"
                ]);
            }

            // Create a collection to hold all items
            $items = collect();

            foreach ($facilityReport->items as $reportItem) {
                if (!$reportItem->product) continue;

                // Get category name from the product's category relationship
                $categoryName = $reportItem->product->category ? $reportItem->product->category->name : 'Uncategorized';

                // Calculate closing balance using LMIS formula: 
                // Opening Balance + Stock Received - Stock Issued + Positive Adjustments - Negative Adjustments
                $calculatedClosingBalance = ($reportItem->opening_balance ?? 0)
                                          + ($reportItem->stock_received ?? 0)
                                          - ($reportItem->stock_issued ?? 0)
                                          + ($reportItem->positive_adjustments ?? 0)
                                          - ($reportItem->negative_adjustments ?? 0);

                // Create a mock item with the report item data
                $mockItem = (object) [
                    'id' => $reportItem->id,
                    'product' => $reportItem->product,
                    'category' => $reportItem->product->category,
                    'category_name' => $categoryName,
                    'opening_balance' => $reportItem->opening_balance ?? 0,
                    'stock_received' => $reportItem->stock_received ?? 0,
                    'stock_issued' => $reportItem->stock_issued ?? 0,
                    'positive_adjustments' => $reportItem->positive_adjustments ?? 0,
                    'negative_adjustments' => $reportItem->negative_adjustments ?? 0,
                    'closing_balance' => $calculatedClosingBalance, // Use calculated value
                    'stored_closing_balance' => $reportItem->closing_balance ?? 0, // Keep original for reference
                ];

                $items->push($mockItem);
            }

            // Sort by the selected type in descending order
            $items = $items->sortByDesc($type);

            if ($items->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No inventory items found for {$month}"
                ]);
            }

            // Process data for chart visualization - chunk by 4 but organized by category (matching warehouse)
            $chartData = $this->processChartData($items, $type);

            return response()->json([
                'success' => true,
                'chartData' => $chartData,
                'items' => $items->map(function ($item) use ($type) {
                    return [
                        'id' => $item->id,
                        'product_name' => $item->product->name,
                        'product_id' => $item->product->id,
                        'category_name' => $item->category_name,
                        'value' => $item->{$type},
                        'opening_balance' => $item->opening_balance,
                        'stock_received' => $item->stock_received,
                        'stock_issued' => $item->stock_issued,
                        'positive_adjustments' => $item->positive_adjustments,
                        'negative_adjustments' => $item->negative_adjustments,
                        'closing_balance' => $item->closing_balance,
                        'stored_closing_balance' => $item->stored_closing_balance,
                    ];
                }),
                'facility_id' => $facilityId,
                'period' => $month,
                'type' => $type
            ]);

        } catch (\Exception $e) {
            Log::error('Error in facilityTracertItems:', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'An error occurred while fetching inventory data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process data for chart visualization - chunk by 4 but organized by category (matching warehouse)
     */
    private function processChartData($items, $type)
    {
        if ($items->isEmpty()) {
            return [
                'charts' => [$this->getEmptyChartData()],
                'totalCharts' => 1
            ];
        }

        // Color palette for charts
        $colors = [
            ['bg' => 'rgba(59, 130, 246, 0.8)', 'border' => 'rgb(59, 130, 246)'], // Blue
            ['bg' => 'rgba(16, 185, 129, 0.8)', 'border' => 'rgb(16, 185, 129)'], // Green
            ['bg' => 'rgba(245, 158, 11, 0.8)', 'border' => 'rgb(245, 158, 11)'], // Yellow
            ['bg' => 'rgba(239, 68, 68, 0.8)', 'border' => 'rgb(239, 68, 68)'], // Red
            ['bg' => 'rgba(147, 51, 234, 0.8)', 'border' => 'rgb(147, 51, 234)'], // Purple
            ['bg' => 'rgba(236, 72, 153, 0.8)', 'border' => 'rgb(236, 72, 153)'], // Pink
            ['bg' => 'rgba(14, 165, 233, 0.8)', 'border' => 'rgb(14, 165, 233)'], // Sky
            ['bg' => 'rgba(34, 197, 94, 0.8)', 'border' => 'rgb(34, 197, 94)'], // Emerald
            ['bg' => 'rgba(168, 85, 247, 0.8)', 'border' => 'rgb(168, 85, 247)'], // Violet
            ['bg' => 'rgba(251, 191, 36, 0.8)', 'border' => 'rgb(251, 191, 36)'] // Amber
        ];

        // Group items by category and create separate charts for each category
        $itemsByCategory = $items->groupBy('category_name');
        $charts = [];
        $chartId = 1;
        
        foreach ($itemsByCategory as $categoryName => $categoryItems) {
            // Sort items within this category by the selected type (descending)
            $sortedCategoryItems = $categoryItems->sortByDesc($type);
            
            // Chunk items within this category by 4
            $categoryChunks = $sortedCategoryItems->chunk(4);
            
            foreach ($categoryChunks as $chunkIndex => $chunk) {
                $labels = [];
                $data = [];
                $backgroundColors = [];
                $borderColors = [];
                
                foreach ($chunk as $index => $item) {
                    // Truncate long product names for better chart display
                    $productName = strlen($item->product->name) > 20 
                        ? substr($item->product->name, 0, 20) . '...'
                        : $item->product->name;
                        
                    $labels[] = $productName;
                    $data[] = (float) $item->{$type};
                    
                    $colorIndex = $index % count($colors);
                    $backgroundColors[] = $colors[$colorIndex]['bg'];
                    $borderColors[] = $colors[$colorIndex]['border'];
                }

                $charts[] = [
                    'id' => $chartId++,
                    'category' => $categoryName,
                    'categoryDisplay' => $categoryName,
                    'labels' => $labels,
                    'data' => $data,
                    'backgroundColors' => $backgroundColors,
                    'borderColors' => $borderColors,
                    'total' => array_sum($data),
                    'count' => count($data)
                ];
            }
        }

        return [
            'charts' => $charts,
            'totalCharts' => count($charts),
            'totalItems' => $items->count()
        ];
    }

    /**
     * Get empty chart data for error states
     */
    private function getEmptyChartData()
    {
        return [
            'id' => 1,
            'category' => 'No Data',
            'categoryDisplay' => 'No Data Available',
            'labels' => ['No Data'],
            'data' => [0],
            'backgroundColors' => ['rgba(156, 163, 175, 0.8)'],
            'borderColors' => ['rgba(156, 163, 175, 1)'],
            'total' => 0,
            'count' => 0
        ];
    }

    /**
     * Format large numbers with k, m abbreviations
     */
    private function formatNumber($number)
    {
        $number = (float) $number;
        
        if ($number >= 1000000) {
            return round($number / 1000000, 1) . 'M';
        } elseif ($number >= 1000) {
            return round($number / 1000, 1) . 'K';
        } else {
            return number_format($number, 0);
        }
    }
} 