<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Support\Facades\Log;

class InventoryEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct()
    {
        Log::debug('InventoryEvent constructor called', [
            'timestamp' => now()->toDateTimeString(),
            'broadcast_driver' => config('broadcasting.default'),
            'trace_id' => uniqid('inv_event_')
        ]);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return Channel|array
     */
    public function broadcastOn()
    {
        $channel = new Channel('inventory');
        
        Log::info('Broadcasting inventory event', [
            'channel' => 'inventory',
            'timestamp' => now()->toDateTimeString(),
            'broadcast_driver' => config('broadcasting.default'),
            'pusher_config' => [
                'key' => config('broadcasting.connections.pusher.key'),
                'cluster' => config('broadcasting.connections.pusher.options.cluster')
            ]
        ]);
        
        return $channel;
    }

    /**
     * The event's broadcast name.
     * 
     * Laravel will prepend a '.' to the event name, so consumers 
     * should listen for '.inventory-updated'
     */
    public function broadcastAs(): string
    {
        Log::debug('InventoryEvent broadcast name: inventory-updated', [
            'event_name' => 'inventory-updated',
            'timestamp' => now()->toDateTimeString()
        ]);
        
        return 'inventory-updated';
    }
    
    /**
     * Get the data to broadcast.
     *
     * @return array
     */
    public function broadcastWith()
    {
        $data = [
            'timestamp' => now()->toDateTimeString(),
            'message' => 'Inventory has been updated'
        ];
        
        Log::debug('InventoryEvent data to broadcast', $data);
        
        return $data;
    }
}
