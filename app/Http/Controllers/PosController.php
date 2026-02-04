<?php

namespace App\Http\Controllers;

use App\Models\Pos;
use App\Models\FacilityInventory;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PosController extends Controller
{
    public function index(Request $request)
    {
        $inventories = FacilityInventory::where('facility_id', auth()->user()->facility_id)
            ->select('product_id')
            ->selectRaw('SUM(quantity) as quantity')
            ->selectRaw('MIN(expiry_date) as expiry_date')
            ->selectRaw('MAX(id) as id')
            ->selectRaw('MAX(facility_id) as facility_id')
            ->selectRaw('MAX(location) as location')
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', Carbon::now())
            ->groupBy('product_id')
            ->with('product')
            ->get();

        logger()->info($request->all());

        $pos = Pos::query();

        // Get all records for today, regardless of time
        $today = Carbon::now()->startOfDay();
        logger()->info($today);
        $pos = $pos->whereBetween('pos_date', [$today, $today->copy()->endOfDay()]);

        if ($request->filled('search_recent')) {
            $pos = $pos->where(function ($query) use ($request) {
                $query->where('patient_name', 'like', '%' . $request->search_recent . '%')
                    ->orWhere('patient_phone', 'like', '%' . $request->search_recent . '%')
                    ->orWhereHas('product', function ($subQuery) use ($request) {
                        $subQuery->where('name', 'like', '%' . $request->search_recent . '%')
                            ->orWhere('barcode', 'like', '%' . $request->search_recent . '%');
                    });
            });
        }
        $pos = $pos->with('product');
        $pos = $pos->get();

        return inertia('Pos/Index', [
            'inventories' => $inventories,
            'pos' => $pos
        ]);
    }

    private function createPrescriptionBatches($productId, $totalQuantity, $data)
    {
        // Get all available batches ordered by expiry date
        $batches = FacilityInventory::where('product_id', $productId)
            ->where('facility_id', auth()->user()->facility_id)
            ->where('quantity', '>', 0)
            ->where('expiry_date', '>', Carbon::now())
            ->orderBy('expiry_date')
            ->get();

        $remainingQuantity = $totalQuantity;
        $prescriptions = [];

        foreach ($batches as $batch) {
            if ($remainingQuantity <= 0) break;

            $quantityFromBatch = min($batch->quantity, $remainingQuantity);

            // Create prescription for this batch
            $prescriptionData = array_merge($data, [
                'total_quantity' => $quantityFromBatch,
                'pos_date' => Carbon::now()->toDateTimeString()
            ]);

            // Create POS record
            $pos = Pos::create($prescriptionData);
            $prescriptions[] = $pos;

            // Update inventory
            $batch->decrement('quantity', $quantityFromBatch);

            // Delete if quantity becomes zero
            if ($batch->fresh()->quantity <= 0) {
                $batch->delete();
            }

            $remainingQuantity -= $quantityFromBatch;
        }

        if ($remainingQuantity > 0) {
            throw new \Exception("Insufficient stock. Only " . ($totalQuantity - $remainingQuantity) . " units available.");
        }

        return $prescriptions;
    }

    public function store(Request $request)
    {
        try {
            DB::beginTransaction();

            $validated = $request->validate([
                'id' => 'nullable|exists:pos,id',
                'product_id' => 'required|exists:products,id',
                'dose' => 'required|numeric',
                'frequency' => 'required|in:1,2,3,4',
                'start_date' => 'required|date',
                'duration' => 'required|integer',
                'total_quantity' => 'required|integer',
                'patient_name' => 'required|string',
                'patient_phone' => 'required|string'
            ]);

            $validated['facility_id'] = auth()->user()->facility_id;

            // Add user information
            $validated['created_by'] = auth()->id();
            $validated['updated_by'] = auth()->id();

            // Create prescriptions from multiple batches if needed
            $prescriptions = $this->createPrescriptionBatches(
                $validated['product_id'],
                $validated['total_quantity'],
                $validated
            );

            DB::commit();
            return response()->json("Prescription saved successfully", 200);
        } catch (\Throwable $th) {
            DB::rollBack();
            return response()->json($th->getMessage(), 500);
        }
    }

    public function getFacilityInventories()
    {
        $inventories = FacilityInventory::select('id', 'name', 'quantity')
            ->where('quantity', '>', 0)
            ->get();

        return response()->json($inventories);
    }
}
