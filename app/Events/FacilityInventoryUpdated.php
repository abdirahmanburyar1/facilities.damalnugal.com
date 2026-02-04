<?php

namespace App\Events;

use App\Models\FacilityInventory;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FacilityInventoryUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $facilityInventory;
    public $action;
    public $facilityId;

    /**
     * Create a new event instance.
     */
    public function __construct(FacilityInventory $facilityInventory, string $action = 'updated')
    {
        $this->facilityInventory = $facilityInventory;
        $this->action = $action;
        $this->facilityId = $facilityInventory->facility_id;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, \Illuminate\Broadcasting\Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new Channel('facility-inventory.' . $this->facilityId),
            new Channel('facility-inventory'),
        ];
    }

    /**
     * The event's broadcast name.
     *
     * @return string
     */
    public function broadcastAs(): string
    {
        return 'FacilityInventoryUpdated';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->facilityInventory->id,
            'facility_id' => $this->facilityInventory->facility_id,
            'product_id' => $this->facilityInventory->product_id,
            'action' => $this->action,
            'updated_at' => $this->facilityInventory->updated_at,
            'items' => $this->facilityInventory->items->map(function ($item) {
                return [
                    'id' => $item->id,
                    'quantity' => $item->quantity,
                    'batch_number' => $item->batch_number,
                    'expiry_date' => $item->expiry_date,
                    'uom' => $item->uom,
                    'barcode' => $item->barcode,
                ];
            }),
        ];
    }
} 