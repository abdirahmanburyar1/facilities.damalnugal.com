<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class GenerateFacilityInventories extends Command
{
    protected $signature = 'generate:facility-inventories';
    protected $description = 'Generate facility inventories data based on inventories table';

    public function handle()
    {
        $inventories = DB::table('inventories')
            ->whereIn('product_id', [1, 2])
            ->get();

        $facilities = DB::table('facilities')->get();

        foreach ($facilities as $facility) {
            foreach ($inventories as $inventory) {
                DB::table('facility_inventories')->insert([
                    'facility_id' => $facility->id,
                    'product_id' => $inventory->product_id,
                    'batch_number' => $inventory->batch_number,
                    'expiry_date' => $inventory->expiry_date,
                    'barcode' => $inventory->barcode,
                    'quantity' => $inventory->quantity,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->info('Facility inventories generated successfully!');
    }
}
