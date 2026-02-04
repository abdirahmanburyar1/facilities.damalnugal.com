<?php

namespace App\Console\Commands;

use App\Events\LowStockNotification;
use App\Models\Inventory;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class CheckLowStock extends Command
{
    protected $signature = 'inventory:check-low-stock';
    protected $description = 'Check for low stock items and send notifications';

    public function handle()
    {
        $lowStockItems = Inventory::where('quantity', '<=', 'reorder_level')
            ->where('is_active', true)
            ->get();

        foreach ($lowStockItems as $inventory) {
            try {
                event(new LowStockNotification($inventory));
                Log::info('Broadcasted low stock notification for inventory ID: ' . $inventory->id);
            } catch (\Exception $e) {
                Log::error('Failed to broadcast low stock notification: ' . $e->getMessage());
            }
        }

        $this->info('Low stock check completed. ' . $lowStockItems->count() . ' items found.');
    }
} 