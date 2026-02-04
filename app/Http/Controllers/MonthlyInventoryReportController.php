<?php

namespace App\Http\Controllers;

use App\Models\FacilityMonthlyReport;
use App\Models\FacilityMonthlyReportItem;
use App\Models\Product;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Carbon\Carbon;
use Illuminate\Support\Facades\Response as ResponseFacade;

class MonthlyInventoryReportController extends Controller
{
    /**
     * Display a listing of monthly reports
     */
    public function index(Request $request): Response
    {
        $facilityId = auth()->user()->facility_id;
        
        $query = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->with(['items.product.category:id,name','items.product.dosage:id,name','facility.handledBy','approvedBy:id,name','submittedBy:id,name','reviewedBy:id,name','rejectedBy:id,name']);
        
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        
        // Note: Product filtering is handled on the frontend to filter items within the report

        // Only fetch reports if both year and month are provided
        if ($request->filled('month_year')) {
            $reports = $query->orderBy('created_at', 'desc')
                            ->first();
        } else {
            // Return empty collection if year or month is not provided
            $reports = collect();
        }

        // Get the current facility to determine its type
        $facility = auth()->user()->facility;
        
        // Get eligible products for this facility type for the filter dropdown
        $products = collect();
        if ($facility) {
            $products = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
        }
        
        $years = range(date('Y'), date('Y') - 5);
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];

        return Inertia::render('MonthlyInventoryReport/Index', [
            'reports' => $reports,
            'filters' => $request->only(['month_year', 'status', 'product_id']),
            'products' => $products,
            'years' => $years,
            'months' => $months,
            'facilityType' => $facility ? $facility->facility_type : null,
        ]);
    }

    /**
     * Show the form for creating a new report or editing existing one
     */
    public function create(Request $request): Response
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        
        // Get the current facility to determine its type
        $facility = auth()->user()->facility;
        if (!$facility) {
            return redirect()->back()->with('error', 'Facility not found for current user.');
        }
        
        // Format the report period
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        // Get or create the monthly report for this period
        $monthlyReport = FacilityMonthlyReport::firstOrCreate([
            'facility_id' => $facilityId,
            'report_period' => $reportPeriod,
        ], [
            'status' => 'draft',
        ]);
        
        // Get eligible products for this facility type
        $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
        
        // Get existing report items for this period

        $existingItems = FacilityMonthlyReportItem::where('parent_id', $monthlyReport->id)
            ->with('product:id,name','product.dosage')
            ->get()
            ->keyBy('product_id');

        $reportData = [];
        foreach ($eligibleProducts as $product) {
            $existing = $existingItems->get($product->id);
            $reportData[] = [
                'product_id' => $product->id,
                'product' => $product,
                'opening_balance' => $existing ? $existing->opening_balance : 0,
                'stock_received' => $existing ? $existing->stock_received : 0,
                'stock_issued' => $existing ? $existing->stock_issued : 0,
                'positive_adjustments' => $existing ? $existing->positive_adjustments : 0,
                'negative_adjustments' => $existing ? $existing->negative_adjustments : 0,
                'closing_balance' => $existing ? $existing->closing_balance : 0,
                'stockout_days' => $existing ? $existing->stockout_days : 0,
                'id' => $existing ? $existing->id : null,
            ];
        }

        return Inertia::render('MonthlyInventoryReport/Create', [
            'reportData' => $reportData,
            'year' => $year,
            'month' => $month,
            'monthName' => $this->getMonthName($month),
            'facility' => $facility,
            'reportId' => $monthlyReport->id,
            'eligibleProductsCount' => $eligibleProducts->count(),
            'facilityType' => $facility->facility_type,
        ]);
    }

    /**
     * Store or update monthly reports
     */
    public function store(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
            'reports' => 'required|array',
            'reports.*.product_id' => 'required|exists:products,id',
            'reports.*.opening_balance' => 'required|numeric|min:0',
            'reports.*.stock_received' => 'required|numeric|min:0',
            'reports.*.stock_issued' => 'required|numeric|min:0',
            'reports.*.positive_adjustments' => 'nullable|numeric|min:0',
            'reports.*.negative_adjustments' => 'nullable|numeric|min:0',
            'reports.*.stockout_days' => 'nullable|integer|min:0',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->year;
        $month = $request->month;
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        // Get or create the monthly report for this period
        $monthlyReport = FacilityMonthlyReport::firstOrCreate([
            'facility_id' => $facilityId,
            'report_period' => $reportPeriod,
        ], [
            'status' => 'draft',
        ]);
        
        $savedCount = 0;
        $errors = [];

        foreach ($request->reports as $reportData) {
            try {
                // Check if this is an update (editing existing item) or creation (initial generation)
                $existingItem = FacilityMonthlyReportItem::where([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                ])->first();

                $data = [
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                    'opening_balance' => $reportData['opening_balance'],
                    'stock_received' => $reportData['stock_received'],
                    'stock_issued' => $reportData['stock_issued'],
                    'positive_adjustments' => $reportData['positive_adjustments'] ?? 0,
                    'negative_adjustments' => $reportData['negative_adjustments'] ?? 0,
                    'stockout_days' => $reportData['stockout_days'] ?? 0,
                ];

                // For new items (initial generation), calculate closing balance manually
                // For existing items (editing), let the model calculate automatically
                if (!$existingItem) {
                    $data['closing_balance'] = $reportData['opening_balance'] 
                        + $reportData['stock_received'] 
                        - $reportData['stock_issued'] 
                        + ($reportData['positive_adjustments'] ?? 0) 
                        - ($reportData['negative_adjustments'] ?? 0);
                }
                // Note: For existing items, closing_balance will be automatically calculated by model

                FacilityMonthlyReportItem::updateOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $reportData['product_id'],
                ], $data);

                $savedCount++;
            } catch (\Exception $e) {
                $errors[] = "Error saving report for product ID {$reportData['product_id']}: " . $e->getMessage();
            }
        }

        if (empty($errors)) {
            return redirect()->route('reports.monthly-reports.index')
                ->with('success', "Successfully saved {$savedCount} monthly reports.");
        } else {
            return redirect()->back()
                ->with('error', 'Some reports could not be saved: ' . implode(', ', $errors))
                ->with('success', "Successfully saved {$savedCount} reports.");
        }
    }

    /**
     * Submit reports for approval
     */
    public function submit(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $reportPeriod = sprintf('%04d-%02d', $request->year, $request->month);
        
        $monthlyReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', $reportPeriod)
            ->where('status', 'draft')
            ->first();

        if (!$monthlyReport) {
            return redirect()->back()->with('error', 'No draft report found for this period.');
        }

        $monthlyReport->update([
            'status' => 'submitted',
            'submitted_at' => now(),
            'submitted_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', "Successfully submitted report for approval.");
    }

    /**
     * Export monthly report as CSV
     */
    public function export(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        $reports = FacilityMonthlyReportItem::with(['product:id,name','product.category:id,name', 'product.dosage:id,name', 'report.facility:id,name'])
            ->whereHas('report', function ($q) use ($facilityId, $reportPeriod) {
                $q->where('facility_id', $facilityId)
                  ->where('report_period', $reportPeriod);
            })
            ->orderBy('product_id')
            ->get();

        if ($reports->isEmpty()) {
            return redirect()->back()->with('error', 'No data found for the selected period.');
        }

        $monthName = $this->getMonthName($month);
        $facilityName = auth()->user()->facility->name ?? 'Unknown Facility';
        
        $filename = "Monthly_Inventory_Report_{$facilityName}_{$monthName}_{$year}.csv";
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function() use ($reports, $monthName, $year, $facilityName) {
            $file = fopen('php://output', 'w');
            
            // Add header information
            fputcsv($file, ["Monthly Summary Report Form: Logistic Data Hospitals & HCs"]);
            fputcsv($file, ["Facility: {$facilityName}"]);
            fputcsv($file, ["Report Period: {$monthName} {$year}"]);
            fputcsv($file, []);
            
            // Add CSV headers
            fputcsv($file, [
                'Item Name',
                'Category',
                'Dosage Form',
                'Opening Balance',
                'Stock Received',
                'Stock Issued',
                'Positive Adjustments',
                'Negative Adjustments',
                'Closing Balance',
                'Stockout Days'
            ]);

            foreach ($reports as $report) {
                fputcsv($file, [
                    $report->product->name ?? 'Unknown Product',
                    $report->product->category->name ?? 'N/A',
                    $report->product->dosage->name ?? 'N/A',
                    $report->opening_balance ?? 0,
                    $report->stock_received ?? 0,
                    $report->stock_issued ?? 0,
                    $report->positive_adjustments ?? 0,
                    $report->negative_adjustments ?? 0,
                    $report->closing_balance ?? 0,
                    $report->stockout_days ?? 0,
                ]);
            }

            fclose($file);
        };

        return ResponseFacade::stream($callback, 200, $headers);
    }

    /**
     * Get summary data for reports
     */
    public function summary(Request $request)
    {
        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);
        
        $monthlyReport = FacilityMonthlyReport::where('facility_id', $facilityId)
            ->where('report_period', $reportPeriod)
            ->first();

        if (!$monthlyReport) {
            return response()->json([
                'total_products' => 0,
                'total_opening_balance' => 0,
                'total_received' => 0,
                'total_issued' => 0,
                'total_closing_balance' => 0,
                'total_stockout_days' => 0,
                'draft_reports' => 0,
                'submitted_reports' => 0,
                'approved_reports' => 0,
            ]);
        }

        $items = $monthlyReport->items();

        $summary = [
            'total_products' => $items->count(),
            'total_opening_balance' => $items->sum('opening_balance') ?? 0,
            'total_received' => $items->sum('stock_received') ?? 0,
            'total_issued' => $items->sum('stock_issued') ?? 0,
            'total_closing_balance' => $items->sum('closing_balance') ?? 0,
            'total_stockout_days' => $items->sum('stockout_days') ?? 0,
            'draft_reports' => $monthlyReport->status === 'draft' ? 1 : 0,
            'submitted_reports' => $monthlyReport->status === 'submitted' ? 1 : 0,
            'approved_reports' => $monthlyReport->status === 'approved' ? 1 : 0,
        ];

        return response()->json($summary);
    }

    /**
     * Generate reports automatically from facility movements
     */
    public function generateReportFromMovements(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year');
        $month = $request->get('month');
        $reportPeriod = sprintf('%04d-%02d', $year, $month);

        try {
            // Get facility and check eligibility
            $facility = auth()->user()->facility;
            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Create date range for the month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();
            $previousMonth = $startDate->copy()->subMonth();

            // Check if report already exists
            $existingReport = FacilityMonthlyReport::where([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
            ])->first();

            if ($existingReport) {
                $monthName = $this->getMonthName($month);
                return response()->json([
                    'success' => false,
                    'message' => "Monthly inventory report for {$monthName} {$year} already exists. You cannot regenerate an existing report.",
                    'existing_report' => [
                        'id' => $existingReport->id,
                        'status' => $existingReport->status,
                        'period' => $reportPeriod,
                        'created_at' => $existingReport->created_at->format('Y-m-d H:i:s')
                    ]
                ], 400);
            }

            // Create the monthly report
            $monthlyReport = FacilityMonthlyReport::create([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
                'status' => 'draft',
            ]);

            // Get facility movements for the month grouped by product
            $movements = \App\Models\FacilityInventoryMovement::where('facility_id', $facilityId)
                ->whereBetween('movement_date', [$startDate, $endDate])
                ->with('product')
                ->get()
                ->groupBy('product_id');

            // Get Dispence data for the month (patient-level dispensing)
            $dispenceData = \App\Models\DispenceItem::join('dispences', 'dispence_items.dispence_id', '=', 'dispences.id')
                ->where('dispences.facility_id', $facilityId)
                ->whereBetween('dispences.dispence_date', [$startDate, $endDate])
                ->select(
                    'dispence_items.product_id',
                    \DB::raw('SUM(dispence_items.quantity) as total_quantity'),
                    \DB::raw('COUNT(DISTINCT dispences.id) as dispense_count')
                )
                ->groupBy('dispence_items.product_id')
                ->get()
                ->keyBy('product_id');

            // Get MohDispense data for the month (inventory-level dispensing)
            $mohDispenseData = \App\Models\MohDispenseItem::join('moh_dispenses', 'moh_dispense_items.moh_dispense_id', '=', 'moh_dispenses.id')
                ->where('moh_dispenses.facility_id', $facilityId)
                ->whereBetween('moh_dispense_items.dispense_date', [$startDate, $endDate])
                ->select(
                    'moh_dispense_items.product_id',
                    \DB::raw('SUM(moh_dispense_items.quantity) as total_quantity'),
                    \DB::raw('COUNT(DISTINCT moh_dispenses.id) as dispense_count')
                )
                ->groupBy('moh_dispense_items.product_id')
                ->get()
                ->keyBy('product_id');

            // Get opening balances from previous month's closing balance
            $previousReportItems = FacilityMonthlyReportItem::whereHas('report', function($q) use ($facilityId, $previousMonth) {
                $q->where('facility_id', $facilityId)
                  ->where('report_period', $previousMonth->format('Y-m'));
            })->get()->keyBy('product_id');

            $createdCount = 0;
            $updatedCount = 0;
            
            foreach ($movements as $productId => $productMovements) {
                $product = $productMovements->first()->product;
                
                // Calculate opening balance from previous month's closing balance
                $openingBalance = 0;
                if (isset($previousReportItems[$productId])) {
                    $openingBalance = $previousReportItems[$productId]->closing_balance;
                }
                // If no previous month data exists, opening balance should be 0

                // Calculate movements
                $stockReceived = $productMovements->where('movement_type', 'facility_received')->sum('facility_received_quantity');
                $stockIssued = $productMovements->where('movement_type', 'facility_issued')->sum('facility_issued_quantity');
                
                // Get MOH dispense movements (these are also facility_issued but from moh_dispense source)
                $mohDispenseIssued = $productMovements->where('movement_type', 'facility_issued')
                    ->where('source_type', 'moh_dispense')
                    ->sum('facility_issued_quantity');
                
                // Get dispense data for this product (for detailed tracking)
                $dispenceQuantity = $dispenceData->get($productId)->total_quantity ?? 0;
                $dispenceCount = $dispenceData->get($productId)->dispense_count ?? 0;
                $mohDispenseQuantity = $mohDispenseData->get($productId)->total_quantity ?? 0;
                $mohDispenseCount = $mohDispenseData->get($productId)->dispense_count ?? 0;
                
                // Total dispensed quantity (both sources) - use movement data for accuracy
                $totalDispensed = $dispenceQuantity + $mohDispenseQuantity;
                
                // Note: MOH dispense quantities should match the movement data
                // This provides dual verification of the data integrity
                
                // Calculate closing balance
                $closingBalance = $openingBalance + $stockReceived - $stockIssued;

                // Create or update report item
                $item = FacilityMonthlyReportItem::updateOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $productId,
                ], [
                    'opening_balance' => $openingBalance,
                    'stock_received' => $stockReceived,
                    'stock_issued' => $stockIssued,
                    'positive_adjustments' => 0,
                    'negative_adjustments' => 0,
                    'closing_balance' => $closingBalance,
                    'stockout_days' => 0, // This would need manual input or separate calculation
                    // Add dispense tracking fields
                    'patient_dispense_quantity' => $dispenceQuantity,
                    'patient_dispense_count' => $dispenceCount,
                    'moh_dispense_quantity' => $mohDispenseQuantity,
                    'moh_dispense_count' => $mohDispenseCount,
                    'total_dispensed_quantity' => $totalDispensed,
                    'total_dispense_count' => $dispenceCount + $mohDispenseCount,
                ]);
                
                if ($item->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            // Also create empty items for products with no movements but are eligible
            $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
            $movementProductIds = $movements->keys()->toArray();
            
            foreach ($eligibleProducts as $product) {
                if (!in_array($product->id, $movementProductIds)) {
                    // Get opening balance from previous month
                    $openingBalance = 0;
                    if (isset($previousReportItems[$product->id])) {
                        $openingBalance = $previousReportItems[$product->id]->closing_balance;
                    }

                    // Get dispense data for this product (even if no movements)
                    $dispenceQuantity = $dispenceData->get($product->id)->total_quantity ?? 0;
                    $dispenceCount = $dispenceData->get($product->id)->dispense_count ?? 0;
                    $mohDispenseQuantity = $mohDispenseData->get($product->id)->total_quantity ?? 0;
                    $mohDispenseCount = $mohDispenseData->get($product->id)->dispense_count ?? 0;
                    $totalDispensed = $dispenceQuantity + $mohDispenseQuantity;

                    $item = FacilityMonthlyReportItem::firstOrCreate([
                        'parent_id' => $monthlyReport->id,
                        'product_id' => $product->id,
                    ], [
                        'opening_balance' => $openingBalance,
                        'stock_received' => 0,
                        'stock_issued' => 0,
                        'positive_adjustments' => 0,
                        'negative_adjustments' => 0,
                        'closing_balance' => $openingBalance,
                        'stockout_days' => 0,
                        // Add dispense tracking fields
                        'patient_dispense_quantity' => $dispenceQuantity,
                        'patient_dispense_count' => $dispenceCount,
                        'moh_dispense_quantity' => $mohDispenseQuantity,
                        'moh_dispense_count' => $mohDispenseCount,
                        'total_dispensed_quantity' => $totalDispensed,
                        'total_dispense_count' => $dispenceCount + $mohDispenseCount,
                    ]);
                    
                    if ($item->wasRecentlyCreated) {
                        $createdCount++;
                    }
                }
            }

            $totalProducts = $createdCount + $updatedCount;
            $totalDispenceQuantity = $dispenceData->sum('total_quantity');
            $totalMohDispenseQuantity = $mohDispenseData->sum('total_quantity');
            $totalDispensed = $totalDispenceQuantity + $totalMohDispenseQuantity;
            
            $message = "Monthly report generated successfully from facility movements and dispense data.";
            if ($createdCount > 0) {
                $message .= " {$createdCount} new items created.";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} existing items updated.";
            }
            if ($totalDispensed > 0) {
                $message .= " Total dispensed: {$totalDispensed} units (Patient: {$totalDispenceQuantity}, MOH: {$totalMohDispenseQuantity}).";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'total_products' => $totalProducts,
                    'report_period' => $reportPeriod,
                    'facility_id' => $facilityId,
                    'movements_processed' => $movements->count(),
                    'dispense_data' => [
                        'patient_dispense_quantity' => $totalDispenceQuantity,
                        'patient_dispense_count' => $dispenceData->count(),
                        'moh_dispense_quantity' => $totalMohDispenseQuantity,
                        'moh_dispense_count' => $mohDispenseData->count(),
                        'total_dispensed_quantity' => $totalDispensed,
                        'total_dispense_count' => $dispenceData->count() + $mohDispenseData->count(),
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly report generation from movements failed: ' . $e->getMessage(), [
                'facility_id' => $facilityId,
                'year' => $year,
                'month' => $month,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report from movements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Trigger report generation via queue from the web interface (legacy - creates empty items)
     */
    public function generateReport(Request $request)
    {
        $request->validate([
            'year' => 'nullable|integer|min:2020|max:2030',
            'month' => 'nullable|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year', date('Y'));
        $month = $request->get('month', date('n'));
        $reportPeriod = sprintf('%04d-%02d', $year, $month);

        try {
            // Check if facility exists
            if (!$facilityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Get the current facility to determine its type
            $facility = auth()->user()->facility;
            if (!$facility) {
                return response()->json([
                    'success' => false,
                    'message' => 'Facility not found for current user.'
                ], 400);
            }

            // Check if report already exists
            $existingReport = FacilityMonthlyReport::where([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
            ])->first();

            if ($existingReport) {
                $monthName = $this->getMonthName($month);
                return response()->json([
                    'success' => false,
                    'message' => "Monthly inventory report for {$monthName} {$year} already exists. You cannot regenerate an existing report.",
                    'existing_report' => [
                        'id' => $existingReport->id,
                        'status' => $existingReport->status,
                        'period' => $reportPeriod,
                        'created_at' => $existingReport->created_at->format('Y-m-d H:i:s')
                    ]
                ], 400);
            }

            // Create the monthly report
            $monthlyReport = FacilityMonthlyReport::create([
                'facility_id' => $facilityId,
                'report_period' => $reportPeriod,
                'status' => 'draft',
            ]);

            // Get eligible products for this facility type
            $eligibleProducts = $facility->eligibleProducts()->select('products.id', 'products.name')->get();
            
            if ($eligibleProducts->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => "No eligible products found for facility type: {$facility->facility_type}"
                ], 400);
            }
            
            $createdCount = 0;
            $updatedCount = 0;
            
            foreach ($eligibleProducts as $product) {
                $item = FacilityMonthlyReportItem::firstOrCreate([
                    'parent_id' => $monthlyReport->id,
                    'product_id' => $product->id,
                ], [
                    'opening_balance' => 0,
                    'stock_received' => 0,
                    'stock_issued' => 0,
                    'positive_adjustments' => 0,
                    'negative_adjustments' => 0,
                    'closing_balance' => 0,
                    'stockout_days' => 0,
                ]);
                
                if ($item->wasRecentlyCreated) {
                    $createdCount++;
                } else {
                    $updatedCount++;
                }
            }

            $message = "Report generation completed successfully.";
            if ($createdCount > 0) {
                $message .= " {$createdCount} new items created.";
            }
            if ($updatedCount > 0) {
                $message .= " {$updatedCount} existing items updated.";
            }

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'created_count' => $createdCount,
                    'updated_count' => $updatedCount,
                    'total_products' => $eligibleProducts->count(),
                    'report_period' => $reportPeriod,
                    'facility_id' => $facilityId,
                    'facility_type' => $facility->facility_type
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Monthly report generation failed: ' . $e->getMessage(), [
                'facility_id' => $facilityId,
                'year' => $year,
                'month' => $month,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate report: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Sync dispense data to existing monthly report
     */
    public function syncDispenseData(Request $request)
    {
        $request->validate([
            'year' => 'required|integer|min:2020|max:2030',
            'month' => 'required|integer|min:1|max:12',
        ]);

        $facilityId = auth()->user()->facility_id;
        $year = $request->get('year');
        $month = $request->get('month');
        $reportPeriod = sprintf('%04d-%02d', $year, $month);

        try {
            // Get existing monthly report
            $monthlyReport = FacilityMonthlyReport::where('facility_id', $facilityId)
                ->where('report_period', $reportPeriod)
                ->first();

            if (!$monthlyReport) {
                return response()->json([
                    'success' => false,
                    'message' => 'No monthly report found for the specified period. Please generate the report first.'
                ], 400);
            }

            // Create date range for the month
            $startDate = Carbon::create($year, $month, 1)->startOfMonth();
            $endDate = Carbon::create($year, $month, 1)->endOfMonth();

            // Get dispense data for the month
            $dispenceData = \App\Models\DispenceItem::join('dispences', 'dispence_items.dispence_id', '=', 'dispences.id')
                ->where('dispences.facility_id', $facilityId)
                ->whereBetween('dispences.dispence_date', [$startDate, $endDate])
                ->select(
                    'dispence_items.product_id',
                    \DB::raw('SUM(dispence_items.quantity) as total_quantity'),
                    \DB::raw('COUNT(DISTINCT dispences.id) as dispense_count')
                )
                ->groupBy('dispence_items.product_id')
                ->get()
                ->keyBy('product_id');

            // Get MohDispense data for the month
            $mohDispenseData = \App\Models\MohDispenseItem::join('moh_dispenses', 'moh_dispense_items.moh_dispense_id', '=', 'moh_dispenses.id')
                ->where('moh_dispenses.facility_id', $facilityId)
                ->whereBetween('moh_dispense_items.dispense_date', [$startDate, $endDate])
                ->select(
                    'moh_dispense_items.product_id',
                    \DB::raw('SUM(moh_dispense_items.quantity) as total_quantity'),
                    \DB::raw('COUNT(DISTINCT moh_dispenses.id) as dispense_count')
                )
                ->groupBy('moh_dispense_items.product_id')
                ->get()
                ->keyBy('product_id');

            $updatedCount = 0;
            $totalDispenceQuantity = $dispenceData->sum('total_quantity');
            $totalMohDispenseQuantity = $mohDispenseData->sum('total_quantity');

            // Update existing report items with dispense data
            $reportItems = FacilityMonthlyReportItem::where('parent_id', $monthlyReport->id)->get();
            
            foreach ($reportItems as $item) {
                $dispenceQuantity = $dispenceData->get($item->product_id)->total_quantity ?? 0;
                $dispenceCount = $dispenceData->get($item->product_id)->dispense_count ?? 0;
                $mohDispenseQuantity = $mohDispenseData->get($item->product_id)->total_quantity ?? 0;
                $mohDispenseCount = $mohDispenseData->get($item->product_id)->dispense_count ?? 0;
                $totalDispensed = $dispenceQuantity + $mohDispenseQuantity;

                $item->update([
                    'patient_dispense_quantity' => $dispenceQuantity,
                    'patient_dispense_count' => $dispenceCount,
                    'moh_dispense_quantity' => $mohDispenseQuantity,
                    'moh_dispense_count' => $mohDispenseCount,
                    'total_dispensed_quantity' => $totalDispensed,
                    'total_dispense_count' => $dispenceCount + $mohDispenseCount,
                ]);

                $updatedCount++;
            }

            $monthName = $this->getMonthName($month);
            $message = "Dispense data synced successfully for {$monthName} {$year}. ";
            $message .= "Updated {$updatedCount} report items. ";
            $message .= "Total dispensed: " . ($totalDispenceQuantity + $totalMohDispenseQuantity) . " units ";
            $message .= "(Patient: {$totalDispenceQuantity}, MOH: {$totalMohDispenseQuantity}).";

            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'updated_count' => $updatedCount,
                    'report_period' => $reportPeriod,
                    'dispense_data' => [
                        'patient_dispense_quantity' => $totalDispenceQuantity,
                        'patient_dispense_count' => $dispenceData->count(),
                        'moh_dispense_quantity' => $totalMohDispenseQuantity,
                        'moh_dispense_count' => $mohDispenseData->count(),
                        'total_dispensed_quantity' => $totalDispenceQuantity + $totalMohDispenseQuantity,
                    ]
                ]
            ]);

        } catch (\Exception $e) {
            \Log::error('Dispense data sync failed: ' . $e->getMessage(), [
                'facility_id' => $facilityId,
                'year' => $year,
                'month' => $month,
                'user_id' => auth()->id(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Failed to sync dispense data: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get month name from number
     */
    private function getMonthName(int $month): string
    {
        $months = [
            1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
            5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
            9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
        ];
        
        return $months[$month] ?? 'Unknown';
    }
}
