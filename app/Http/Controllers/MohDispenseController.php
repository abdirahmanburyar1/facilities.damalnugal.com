<?php

namespace App\Http\Controllers;

use App\Models\MohDispense;
use App\Imports\MohDispenseImport;
use App\Exports\MohDispenseTemplateExport;
use App\Exceptions\MohDispenseInsufficientInventoryException;
use App\Services\MohDispenseInventoryService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;

class MohDispenseController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = MohDispense::with(['facility', 'createdBy', 'items'])
                ->where('facility_id', auth()->user()->facility_id);

            // Search
            if ($request->search) {
                $query->where('moh_dispense_number', 'like', '%' . $request->search . '%');
            }

            // Filter by status
            if ($request->status) {
                $query->where('status', $request->status);
            }

            $mohDispenses = $query->orderBy('created_at', 'desc')->paginate(15);

            return Inertia::render('MohDispense/Index', [
                'mohDispenses' => $mohDispenses,
                'filters' => $request->only(['search', 'status']),
            ]);
        
        } catch (\Exception $e) {
            \Log::error('MOH Dispense index error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error loading MOH dispenses: ' . $e->getMessage()
            ], 500);
        }
    }

    public function create()
    {
        return Inertia::render('MohDispense/Create');
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'excel_file' => 'required|file|max:10240', // 10MB max
            ]);

            // Additional file type validation
            $file = $request->file('excel_file');
            $allowedExtensions = ['xlsx', 'xls', 'csv'];
            $extension = strtolower($file->getClientOriginalExtension());
            
            // Also check MIME type as fallback
            $allowedMimeTypes = [
                'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
                'application/vnd.ms-excel', // .xls
                'text/csv', // .csv
                'application/csv', // .csv alternative
                'text/plain', // .csv sometimes reported as this
                'application/octet-stream' // fallback for some systems
            ];
            
            $mimeType = $file->getMimeType();
            
            if (!in_array($extension, $allowedExtensions) && !in_array($mimeType, $allowedMimeTypes)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid file type. Please upload an Excel file (.xlsx, .xls) or CSV file (.csv). Detected: ' . $extension . ' (' . $mimeType . ')'
                ], 422);
            }

            // Wrap the entire upload and inventory deduction in a transaction
            return DB::transaction(function () use ($request, $file) {
                // Create MOH dispense record
                $mohDispense = MohDispense::create([
                    'facility_id' => auth()->user()->facility_id,
                    'created_by' => auth()->user()->id,
                    'status' => 'draft',
                ]);

                // Process the Excel file directly
                Excel::import(new MohDispenseImport($mohDispense->id), $file);

                // Immediately deduct from facility inventory
                $inventoryService = new MohDispenseInventoryService();
                $result = $inventoryService->processMohDispense($mohDispense->id);

                if (!$result['success']) {
                    $message = $result['message'] ?? 'Insufficient inventory.';
                    $insufficientItems = $result['insufficient_items'] ?? [];
                    throw new \App\Exceptions\MohDispenseInsufficientInventoryException($message, $insufficientItems);
                }

                return response()->json([
                    'success' => true,
                    'message' => 'Excel file processed and inventory deducted successfully!',
                    'moh_dispense_id' => $mohDispense->id,
                    'moh_dispense_number' => $mohDispense->moh_dispense_number,
                    'status' => 'processed',
                ]);
            });

        } catch (MohDispenseInsufficientInventoryException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
                'insufficient_items' => $e->getInsufficientItems(),
            ], 422);
        } catch (\Exception $e) {
            \Log::error('MOH Dispense upload error: ' . $e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Server error: ' . $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        $mohDispense = MohDispense::with(['facility', 'createdBy', 'items.product'])
            ->where('facility_id', auth()->user()->facility_id)
            ->findOrFail($id);

        return Inertia::render('MohDispense/Show', [
            'mohDispense' => $mohDispense,
        ]);
    }

    public function process($id)
    {
        try {
            $mohDispense = MohDispense::where('facility_id', auth()->user()->facility_id)
                ->findOrFail($id);

            if ($mohDispense->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft dispenses can be processed.',
                ], 400);
            }

            // Use the inventory service to process the MOH dispense
            $inventoryService = new MohDispenseInventoryService();
            $result = $inventoryService->processMohDispense($id);

            return response()->json($result);

        } catch (\Exception $e) {
            \Log::error('MOH Dispense process error: ' . $e->getMessage(), [
                'moh_dispense_id' => $id,
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error processing MOH dispense: ' . $e->getMessage(),
            ], 500);
        }
    }


    /**
     * Validate inventory before processing MOH dispense
     */
    public function validateInventory($id)
    {
        try {
            $mohDispense = MohDispense::where('facility_id', auth()->user()->facility_id)
                ->findOrFail($id);

            if ($mohDispense->status !== 'draft') {
                return response()->json([
                    'success' => false,
                    'message' => 'Only draft dispenses can be validated.',
                ], 400);
            }

            $inventoryService = new MohDispenseInventoryService();
            $result = $inventoryService->validateInventory($id);

            return response()->json([
                'success' => true,
                'data' => $result
            ]);

        } catch (\Exception $e) {
            \Log::error('MOH Dispense validation error: ' . $e->getMessage(), [
                'moh_dispense_id' => $id,
                'user_id' => auth()->id(),
                'facility_id' => auth()->user()->facility_id ?? 'N/A',
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json([
                'success' => false,
                'message' => 'Error validating MOH dispense: ' . $e->getMessage(),
                'debug' => [
                    'error_line' => $e->getLine(),
                    'error_file' => basename($e->getFile()),
                ]
            ], 500);
        }
    }

    public function downloadTemplate()
    {
        $fileName = 'moh_dispense_template.xlsx';
        $facilityId = auth()->user()->facility_id ?? null;
        return Excel::download(new MohDispenseTemplateExport($facilityId), $fileName);
    }
}