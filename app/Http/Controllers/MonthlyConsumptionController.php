<?php

namespace App\Http\Controllers;

use App\Imports\MonthlyConsumptionImport;
use App\Exports\MonthlyConsumptionTemplateExport;
use App\Models\MonthlyConsumptionReport;
use App\Models\Product;
use App\Services\AMCService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;
use Inertia\Inertia;

class MonthlyConsumptionController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $facility = $user?->facility;

        if (!$facility) {
            return redirect()->route('inventories.index')
                ->withErrors(['error' => 'User is not associated with any facility.']);
        }

        $currentYear = now()->year;
        $years = range($currentYear - 3, $currentYear + 1);

        return Inertia::render('Reports/MonthlyConsumption', [
            'facility' => [
                'id' => $facility->id,
                'name' => $facility->name,
                'facility_type' => $facility->facility_type,
            ],
            'currentYear' => $currentYear,
            'currentMonth' => now()->month,
            'yearOptions' => array_values($years),
        ]);
    }

    public function data(Request $request)
    {
        $request->validate([
            'year' => 'required|integer',
        ]);

        $user = $request->user();
        $facility = $user?->facility;

        if (!$facility) {
            return response()->json([
                'success' => false,
                'message' => 'User is not associated with any facility.',
            ], 422);
        }

        $year = (int) $request->year;
        $facilityId = $facility->id;

        // Build months metadata
        $months = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthKey = sprintf('%04d-%02d', $year, $m);
            $months[] = [
                'key' => $monthKey,
                'label' => now()->setDate($year, $m, 1)->format('M-y'),
            ];
        }

        // All reports for this facility/year
        $reports = MonthlyConsumptionReport::where('facility_id', $facilityId)
            ->where('month_year', 'like', $year . '-%')
            ->get();

        $reportIds = $reports->pluck('id');

        // Map of product_id => [month_year => quantity]
        $quantityMap = [];
        if ($reportIds->isNotEmpty()) {
            $items = \App\Models\MonthlyConsumptionItem::with(['report'])
                ->whereIn('parent_id', $reportIds)
                ->get();

            foreach ($items as $item) {
                if (!$item->report) {
                    continue;
                }
                $monthYear = $item->report->month_year;
                $quantityMap[$item->product_id][$monthYear] = (float) $item->quantity;
            }
        }

        // Eligible products for this facility (by facility_type)
        $eligibleProducts = Product::with(['category', 'dosage'])
            ->whereHas('eligible', function ($q) use ($facility) {
                $q->where('facility_type', $facility->facility_type);
            })
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $amcService = new AMCService();

        $rows = [];
        foreach ($eligibleProducts as $product) {
            $row = [
                'product_id' => $product->id,
                'item' => $product->name,
                'category' => optional($product->category)->name,
                'dosage_form' => optional($product->dosage)->name,
                'amc' => null,
                'quantities' => [],
            ];

            foreach ($months as $month) {
                $key = $month['key'];
                $qty = $quantityMap[$product->id][$key] ?? null;
                $row['quantities'][$key] = $qty;
            }

            // Calculate AMC using the same 70% screening logic (dynamic, not stored)
            $amcResult = $amcService->calculateScreenedAMC($facilityId, $product->id);
            $row['amc'] = $amcResult['amc'];

            $rows[] = $row;
        }

        $message = '';
        if (empty($rows)) {
            $message = 'No monthly consumption data to display for this year.';
        }

        return response()->json([
            'success' => true,
            'months' => $months,
            'rows' => $rows,
            'message' => $message,
        ]);
    }

    public function upload(Request $request)
    {
        try {
            if (!$request->hasFile('file')) {
                return response()->json([
                    'success' => false,
                    'message' => 'No file was uploaded',
                ], 422);
            }

            $file = $request->file('file');

            $extension = $file->getClientOriginalExtension();
            if (!$file->isValid() || !in_array(strtolower($extension), ['xlsx', 'xls'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Please upload an Excel file (.xlsx or .xls).',
                ], 422);
            }

            if ($file->getSize() > 20 * 1024 * 1024) {
                return response()->json([
                    'success' => false,
                    'message' => 'File size too large. Maximum allowed size is 20MB.',
                ], 422);
            }

            $user = $request->user();
            $facilityId = $user?->facility_id;

            if (!$facilityId) {
                return response()->json([
                    'success' => false,
                    'message' => 'User is not associated with any facility.',
                ], 422);
            }

            $result = DB::transaction(function () use ($file, $facilityId, $user) {
                set_time_limit(120);

                $import = new MonthlyConsumptionImport((int) $facilityId, $user->id);
                Excel::import($import, $file);

                if ($import->processedRows === 0) {
                    throw new \Exception('No rows were found in the file.', 422);
                }

                return $import;
            });

            $missingSample = $result->missingProductsSample;

            $message = "Processed {$result->processedRows} row(s). ";
            $message .= "Updated {$result->updatedCount} item-month quantities across {$result->createdReports} new report(s).";

            if (!empty($missingSample)) {
                $message .= ' Some items could not be matched: ' . implode(', ', $missingSample);
            }

            Log::info('Monthly consumption upload completed', [
                'facility_id' => $facilityId,
                'user_id' => $user->id,
                'processed_rows' => $result->processedRows,
                'updated_count' => $result->updatedCount,
                'created_reports' => $result->createdReports,
                'missing_products_sample' => $missingSample,
            ]);

            return response()->json([
                'success' => true,
                'message' => $message,
            ]);
        } catch (\Exception $e) {
            Log::error('Monthly consumption upload failed', [
                'error' => $e->getMessage(),
            ]);

            $status = (int) $e->getCode() === 422 ? 422 : 500;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $status);
        }
    }

    public function template(Request $request)
    {
        $user = $request->user();
        $facility = $user?->facility;

        if (!$facility) {
            abort(403, 'User is not associated with any facility.');
        }

        $year = (int) ($request->query('year') ?? now()->year);

        $fileName = sprintf(
            'Monthly_Consumption_Template_%s_%d.xlsx',
            str_replace(' ', '_', $facility->name),
            $year
        );

        return Excel::download(
            new MonthlyConsumptionTemplateExport($facility->id, $year),
            $fileName
        );
    }
}

