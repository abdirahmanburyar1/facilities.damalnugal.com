<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Events\InventoryEvent;
use Illuminate\Support\Facades\Log;

class TestReverbBroadcasting extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'reverb:test {--event=inventory : Event to test (inventory, order, transfer)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Test Reverb broadcasting functionality';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('🧪 Testing Reverb Broadcasting...');
        
        // Check broadcasting configuration
        $this->info('📋 Broadcasting Configuration:');
        $this->line('Default Driver: ' . config('broadcasting.default'));
        $this->line('Reverb Key: ' . config('broadcasting.connections.reverb.key'));
        $this->line('Reverb Host: ' . config('broadcasting.connections.reverb.host'));
        $this->line('Reverb Port: ' . config('broadcasting.connections.reverb.port'));
        
        $eventType = $this->option('event');
        
        try {
            switch ($eventType) {
                case 'inventory':
                    $this->testInventoryEvent();
                    break;
                case 'order':
                    $this->testOrderEvent();
                    break;
                case 'transfer':
                    $this->testTransferEvent();
                    break;
                default:
                    $this->error("Unknown event type: {$eventType}");
                    return 1;
            }
            
            $this->info('✅ Reverb broadcasting test completed successfully!');
            $this->info('📡 Check your browser console to see if the event was received.');
            
        } catch (\Exception $e) {
            $this->error('❌ Reverb broadcasting test failed: ' . $e->getMessage());
            Log::error('Reverb test failed', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return 1;
        }
        
        return 0;
    }
    
    private function testInventoryEvent()
    {
        $this->info('📦 Broadcasting InventoryEvent...');
        broadcast(new InventoryEvent());
        $this->line('Event broadcasted to channel: inventory');
        $this->line('Event name: inventory-updated');
    }
    
    private function testOrderEvent()
    {
        $this->info('📋 Broadcasting OrderEvent...');
        broadcast(new \App\Events\OrderEvent());
        $this->line('Event broadcasted to channel: orders');
        $this->line('Event name: order-updated');
    }
    
    private function testTransferEvent()
    {
        $this->info('🚚 Broadcasting TransferStatusChanged...');
        broadcast(new \App\Events\TransferStatusChanged(1, 'test'));
        $this->line('Event broadcasted to channel: transfer.1');
        $this->line('Event name: transfer-status-changed');
    }
} 