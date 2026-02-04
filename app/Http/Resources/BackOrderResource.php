<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackOrderResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'back_order_number' => $this->back_order_number,
            'back_order_date' => $this->back_order_date,
            'status' => $this->status,
            'notes' => $this->notes,
            'attach_documents' => $this->attach_documents,
            'total_items' => $this->total_items,
            'total_quantity' => $this->total_quantity,
            'source_type' => $this->source_type,
            'created_by' => $this->created_by,
            'updated_by' => $this->updated_by,
            'reported_by' => $this->reported_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'order' => $this->whenLoaded('order', function() {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'order_type' => $this->order->order_type,
                ];
            }),
            'transfer' => $this->whenLoaded('transfer', function() {
                return [
                    'id' => $this->transfer->id,
                    'transfer_id' => $this->transfer->transfer_id,
                    'transfer_type' => $this->transfer->transfer_type,
                ];
            }),
            'creator' => $this->whenLoaded('creator', function() {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
            'updater' => $this->whenLoaded('updater', function() {
                return [
                    'id' => $this->updater->id,
                    'name' => $this->updater->name,
                ];
            }),
            'differences' => $this->whenLoaded('differences', function() {
                return $this->differences->map(function($difference) {
                    return [
                        'id' => $difference->id,
                        'product_id' => $difference->product_id,
                        'quantity' => $difference->quantity,
                        'finalized' => $difference->finalized,
                        'status' => $difference->status,
                        'notes' => $difference->notes,
                        'created_at' => $difference->created_at,
                        'updated_at' => $difference->updated_at,
                        'product' => $difference->product ? [
                            'id' => $difference->product->id,
                            'name' => $difference->product->name,
                            'productID' => $difference->product->productID,
                        ] : null,
                        'inventoryAllocation' => $difference->inventoryAllocation ? [
                            'id' => $difference->inventoryAllocation->id,
                            'barcode' => $difference->inventoryAllocation->barcode,
                            'batch_number' => $difference->inventoryAllocation->batch_number,
                            'expiry_date' => $difference->inventoryAllocation->expiry_date,
                            'uom' => $difference->inventoryAllocation->uom,
                            'allocated_quantity' => $difference->inventoryAllocation->allocated_quantity,
                            'order_item' => $difference->inventoryAllocation->order_item ? [
                                'id' => $difference->inventoryAllocation->order_item->id,
                                'order' => $difference->inventoryAllocation->order_item->order ? [
                                    'id' => $difference->inventoryAllocation->order_item->order->id,
                                    'order_number' => $difference->inventoryAllocation->order_item->order->order_number,
                                    'order_type' => $difference->inventoryAllocation->order_item->order->order_type,
                                ] : null,
                            ] : null,
                            'transfer_item' => $difference->inventoryAllocation->transfer_item ? [
                                'id' => $difference->inventoryAllocation->transfer_item->id,
                                'transfer' => $difference->inventoryAllocation->transfer_item->transfer ? [
                                    'id' => $difference->inventoryAllocation->transfer_item->transfer->id,
                                    'transfer_id' => $difference->inventoryAllocation->transfer_item->transfer->transfer_id,
                                    'transfer_type' => $difference->inventoryAllocation->transfer_item->transfer->transfer_type,
                                ] : null,
                            ] : null,
                        ] : null,
                    ];
                });
            }),
        ];
    }
}
