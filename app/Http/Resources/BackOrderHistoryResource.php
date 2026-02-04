<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BackOrderHistoryResource extends JsonResource
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
            'reported_by' => $this->reported_by,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            
            // Relationships
            'order' => $this->whenLoaded('order', function () {
                return [
                    'id' => $this->order->id,
                    'order_number' => $this->order->order_number,
                    'order_type' => $this->order->order_type,
                    'facility' => $this->order->facility ? [
                        'id' => $this->order->facility->id,
                        'name' => $this->order->facility->name,
                    ] : null,
                ];
            }),
            
            'transfer' => $this->whenLoaded('transfer', function () {
                return [
                    'id' => $this->transfer->id,
                    'transfer_id' => $this->transfer->transfer_id,
                ];
            }),
            
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->name,
                ];
            }),
        ];
    }
} 