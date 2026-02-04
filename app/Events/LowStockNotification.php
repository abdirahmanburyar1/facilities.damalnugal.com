<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LowStockNotification
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $inventory;

    public function __construct($inventory)
    {
        $this->inventory = $inventory;
    }

    public function broadcastOn()
    {
        return new Channel('inventory-updates');
    }

    public function broadcastAs()
    {
        return 'LowStockNotification';
    }

    public function broadcastWith()
    {
        return [
            'inventory' => $this->inventory->load('product'),
        ];
    }
} 