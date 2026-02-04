<?php

namespace App\Http\Controllers;

use App\Models\FacilityInventoryMovement;
use App\Models\Product;
use App\Models\Facility;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class FacilityInventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        $query = FacilityInventoryMovement::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->with([
                'product:id,name,category_id',
                'product.category:id,name',
                'facility:id,name',
                'createdBy:id,name'
            ])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply filters
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('movement_date', [
                $request->date_from,
                $request->date_to
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('batch_number', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('product', function($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $movements = $query->paginate($request->input('per_page', 25))
            ->withQueryString();

        // Get summary statistics
        $summaryQuery = FacilityInventoryMovement::where('facility_id', auth()->user()->facility_id);
        
        if ($request->filled('date_from') && $request->filled('date_to')) {
            $summaryQuery->whereBetween('movement_date', [
                $request->date_from,
                $request->date_to
            ]);
        } else {
            // Default to current month if no date range specified
            $summaryQuery->whereBetween('movement_date', [
                Carbon::now()->startOfMonth(),
                Carbon::now()->endOfMonth()
            ]);
        }

        $summary = [
            'total_received' => $summaryQuery->clone()->sum('facility_received_quantity'),
            'total_issued' => $summaryQuery->clone()->sum('facility_issued_quantity'),
            'total_movements' => $summaryQuery->clone()->count(),
            'transfers_count' => $summaryQuery->clone()->where('source_type', 'transfer')->count(),
            'orders_count' => $summaryQuery->clone()->where('source_type', 'order')->count(),
            'dispenses_count' => $summaryQuery->clone()->where('source_type', 'dispense')->count(),
        ];

        // Get filter options
        $products = Product::select('id', 'name')
            ->orderBy('name')
            ->get();

        return Inertia::render('Reports/FacilityInventoryMovements', [
            'movements' => $movements,
            'summary' => $summary,
            'products' => $products,
            'filters' => $request->only([
                'movement_type', 
                'source_type', 
                'product_id', 
                'date_from', 
                'date_to', 
                'search',
                'per_page'
            ]),
            'movement_types' => [
                'facility_received' => 'Facility Received',
                'facility_issued' => 'Facility Issued'
            ],
            'source_types' => [
                'transfer' => 'Transfer',
                'order' => 'Order',
                'dispense' => 'Dispense',
                'moh_dispense' => 'MOH Dispense'
            ]
        ]);
    }

    public function export(Request $request)
    {
        $query = FacilityInventoryMovement::query()
            ->where('facility_id', auth()->user()->facility_id)
            ->with([
                'product:id,name,category_id',
                'product.category:id,name',
                'facility:id,name',
                'createdBy:id,name'
            ])
            ->orderBy('movement_date', 'desc')
            ->orderBy('created_at', 'desc');

        // Apply same filters as index
        if ($request->filled('movement_type')) {
            $query->where('movement_type', $request->movement_type);
        }

        if ($request->filled('source_type')) {
            $query->where('source_type', $request->source_type);
        }

        if ($request->filled('product_id')) {
            $query->where('product_id', $request->product_id);
        }

        if ($request->filled('date_from') && $request->filled('date_to')) {
            $query->whereBetween('movement_date', [
                $request->date_from,
                $request->date_to
            ]);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('reference_number', 'like', "%{$search}%")
                  ->orWhere('batch_number', 'like', "%{$search}%")
                  ->orWhere('barcode', 'like', "%{$search}%")
                  ->orWhereHas('product', function($productQuery) use ($search) {
                      $productQuery->where('name', 'like', "%{$search}%");
                  });
            });
        }

        $movements = $query->get();

        $csvData = $movements->map(function ($movement) {
            return [
                'Date' => $movement->movement_date ? Carbon::parse($movement->movement_date)->format('Y-m-d') : '',
                'Product' => $movement->product->name ?? '',
                'Category' => $movement->product->category->name ?? '',
                'Movement Type' => ucfirst(str_replace('_', ' ', $movement->movement_type)),
                'Source Type' => ucfirst($movement->source_type),
                'Reference Number' => $movement->reference_number ?? '',
                'Batch Number' => $movement->batch_number ?? '',
                'Barcode' => $movement->barcode ?? '',
                'UoM' => $movement->uom ?? '',
                'Received Quantity' => $movement->facility_received_quantity ?? 0,
                'Issued Quantity' => $movement->facility_issued_quantity ?? 0,
                'Expiry Date' => $movement->expiry_date ? Carbon::parse($movement->expiry_date)->format('Y-m-d') : '',
                'Notes' => $movement->notes ?? '',
                'Created By' => $movement->createdBy->name ?? '',
                'Created At' => $movement->created_at->format('Y-m-d H:i:s'),
            ];
        });

        $filename = 'facility_inventory_movements_' . Carbon::now()->format('Y_m_d_H_i_s') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $csv = implode(',', array_keys($csvData->first()->toArray())) . "\n";
        foreach ($csvData as $row) {
            $csv .= implode(',', array_map(function($field) {
                return '"' . str_replace('"', '""', $field) . '"';
            }, $row->toArray())) . "\n";
        }

        return response($csv, 200, $headers);
    }
}