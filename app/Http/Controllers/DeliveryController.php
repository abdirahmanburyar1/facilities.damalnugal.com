<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\DispatchInfo;
use App\Models\OrderItem;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;

class DeliveryController extends Controller
{
    /**
     * Mark order as delivered with delivery form data
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function markDelivered(Request $request)
    {
        try {
            DB::beginTransaction();
            
            // Validate request
            $request->validate([
                'order_id' => 'required|exists:orders,id',
                'received_cartoons' => 'required|json',
                'notes' => 'nullable|string',
                'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // 10MB max per image
            ]);
            
            $order = Order::with(['dispatch', 'items'])->findOrFail($request->order_id);
            
            // Validate order status
            if ($order->status !== 'dispatched') {
                return response()->json('Order must be in dispatched status to mark as delivered', 400);
            }
            
            $receivedCartoons = json_decode($request->received_cartoons, true);
            $notes = $request->notes;
            

            

            
            // Handle image uploads - save to public/delivery-images and store FULL URL
            $imagePaths = [];
            if ($request->hasFile('images')) {
                $images = $request->file('images');
                $destination = public_path('delivery-images');

                // Ensure destination exists with proper permissions
                if (!File::exists($destination)) {
                    try {
                        File::makeDirectory($destination, 0755, true, true);
                    } catch (\Exception $e) {
                        DB::rollBack();
                        return response()->json('Failed to prepare uploads directory: ' . $e->getMessage(), 500);
                    }
                }

                foreach ($images as $image) {
                    if ($image->isValid()) {
                        $filename = 'delivery_' . time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
                        // Move file to public/delivery-images
                        $image->move($destination, $filename);

                        // Build full URL using APP_URL, fallback to request host
                        $baseUrl = rtrim(config('app.url') ?: $request->getSchemeAndHttpHost(), '/');
                        $fullUrl = $baseUrl . '/delivery-images/' . $filename;
                        $imagePaths[] = $fullUrl;
                    }
                }
            }
            
            // Update dispatch info with received cartons and images
            if ($order->dispatch && count($order->dispatch) > 0) {
                foreach ($order->dispatch as $dispatch) {
                    if (isset($receivedCartoons[$dispatch->id])) {
                        $dispatch->received_cartons = $receivedCartoons[$dispatch->id];
                        // Save images to the first dispatch record
                        if (!empty($imagePaths)) {
                            $dispatch->image = json_encode($imagePaths);
                        }
                        $dispatch->save();
                    }
                }
            }
            
            // Update order status to delivered
            $order->status = 'delivered';
            $order->delivered_at = Carbon::now();
            $order->delivered_by = auth()->user()->id;
            

            
            $order->save();
            
            DB::commit();
            
            return response()->json([
                'success' => true,
                'message' => 'Order marked as delivered successfully',
                'data' => [
                    'order_id' => $order->id,
                    'status' => $order->status,
                    'delivered_at' => $order->delivered_at,
                    'images_uploaded' => count($imagePaths)
                ]
            ], 200);
            
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'success' => false,
                'message' => 'Failed to mark order as delivered: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get delivery information for an order
     *
     * @param Request $request
     * @param int $orderId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDeliveryInfo(Request $request, $orderId)
    {
        try {
            $order = Order::with(['dispatch', 'items.product.category'])
                ->findOrFail($orderId);
            
            return response()->json([
                'success' => true,
                'data' => [
                    'order' => $order,
                    'dispatch_info' => $order->dispatch,
                    'items' => $order->items
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get delivery information: ' . $e->getMessage()
            ], 500);
        }
    }
} 