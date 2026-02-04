<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class FacilityInventoryTestEvent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $facilityId;
    public $productId;
    public $action;
    public $timestamp;

    /**
     * Create a new event instance.
     */
    public function __construct($facilityId, $productId, $action = 'test')
    {
        $this->facilityId = $facilityId;
        $this->productId = $productId;
        $this->action = $action;
        $this->timestamp = now()->toISOString();
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
        return 'FacilityInventoryTestEvent';
    }

    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith(): array
    {
        return [
            'facility_id' => $this->facilityId,
            'product_id' => $this->productId,
            'action' => $this->action,
            'timestamp' => $this->timestamp,
            'message' => 'Test event from TransferController updateQuantity method',
        ];
    }
} 