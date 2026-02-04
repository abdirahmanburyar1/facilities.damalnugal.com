<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Test route for facility inventory events
Route::post('/test-facility-inventory-event', function () {
    $facilityId = request('facility_id', 1);
    $productId = request('product_id', 1);
    
    event(new \App\Events\FacilityInventoryTestEvent($facilityId, $productId, 'manual_test'));
    
    return response()->json([
        'message' => 'Test event dispatched',
        'facility_id' => $facilityId,
        'product_id' => $productId,
        'timestamp' => now()->toISOString()
    ]);
});

// Test route for InventoryUpdated event
Route::post('/test-inventory-updated', function (Request $request) {
    $facilityId = $request->input('facility_id', auth()->user()->facility_id ?? 1);
    
    Log::info('[TEST-DEBUG] Manually triggering InventoryUpdated event', [
        'facility_id' => $facilityId,
        'user_id' => auth()->id(),
        'timestamp' => now()->toISOString()
    ]);
    
    event(new \App\Events\InventoryUpdated($facilityId));
    
    return response()->json([
        'message' => 'InventoryUpdated event dispatched successfully',
        'facility_id' => $facilityId,
        'timestamp' => now()->toISOString()
    ]);
});
