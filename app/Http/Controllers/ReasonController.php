<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reason;

class ReasonController extends Controller
{
    public function getReasons()
    {
        try {
            $reasons = Reason::pluck('name')->toArray();
            return response()->json($reasons, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function store(Request $request)
    {
        try {
            $request->validate([
                'id' => 'nullable|exists:reasons,id',
                'name' => 'required|string|max:255',
            ]);
            $reason = Reason::updateOrCreate([
                'id' => $request->id,
            ],['name' => $request->name]);
            return response()->json($reason->name, 201);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    public function destroy(Request $request)
    {
        try {
            $request->validate([
                'id' => 'required|exists:reasons,id',
            ]);
            $reason = Reason::find($request->id);
            $reason->delete();
            return response()->json($reason, 200);
        } catch (\Throwable $th) {
            return response()->json($th->getMessage(), 500);
        }
    }

    // index
    public function index(Request $request)
    {
        $reasons = Reason::query();

        if($request->filled('search')){
            $reasons->where('name', 'like', '%'.$request->search.'%');
        }

        $reasons = $reasons->get();
        return response()->json($reasons, 200);
    }
}
