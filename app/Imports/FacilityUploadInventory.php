<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\Facility;
use App\Models\FacilityInventory;
use App\Models\FacilityInventoryItem;
use App\Models\EligibleItem;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Events\AfterImport;
use Carbon\Carbon;

class FacilityUploadInventory implements 
    ToModel, 
    WithHeadingRow, 
    WithChunkReading, 
    WithBatchInserts, 
    SkipsEmptyRows, 
    WithEvents,
    ShouldQueue
{
    use Queueable, InteractsWithQueue;

    public $importId;
    public $facilityId;

    public function __construct(string $importId, int $facilityId)
    {
        $this->importId = $importId;
        $this->facilityId = $facilityId;
        
        Log::info('FacilityUploadInventory initialized', [
            'import_id' => $importId,
            'facility_id' => $this->facilityId
        ]);
    }

    public function model(array $row)
    {
        try {
            // Check if required fields are present
            if (empty($row['item']) || empty($row['quantity']) || empty($row['batch_no']) || empty($row['expiry_date'])) {
                Log::warning('Skipping row due to missing required fields', ['row' => $row]);
                return null;
            }

            // Check if item exists in eligible_items table
            $eligibleItem = $this->getEligibleItem($row['item']);
            if (!$eligibleItem) {
                return null;
            }

            // Get the product from eligible item
            $product = $eligibleItem->product;
            if (!$product) {
                Log::error('Product not found for eligible item', [
                    'item' => $row['item'],
                    'eligible_item_id' => $eligibleItem->id
                ]);
                return null;
            }

            $inventory = $this->getInventory($product->id);
            $expiryDate = $this->parseExpiryDate($row['expiry_date']);
            $batchNumber = trim($row['batch_no']);
            
            // Check if item already exists
            $item = FacilityInventoryItem::where('batch_number', $batchNumber)
                ->where('facility_inventory_id', $inventory->id)
                ->where('product_id', $product->id)
                ->first();

            if($item){
                // Update existing item
                $oldQuantity = $item->quantity;
                $item->increment('quantity', (float) $row['quantity']);
                Log::info('Updated existing facility inventory item', [
                    'product' => $product->name,
                    'batch' => $batchNumber,
                    'quantity_added' => $row['quantity'],
                    'old_quantity' => $oldQuantity,
                    'new_quantity' => $item->fresh()->quantity
                ]);
            } else {
                // Create new item
                $item = FacilityInventoryItem::create([
                    'facility_inventory_id' => $inventory->id,
                    'product_id' => $product->id,
                    'quantity' => (float) $row['quantity'],
                    'expiry_date' => $expiryDate,
                    'warehouse_id' => 1,
                    'uom' => $row['uom'] ?? null,
                    'batch_number' => $batchNumber,
                    'location' => $row['location'] ?? null,
                    'unit_cost' => 0.00,
                    'total_cost' => 0.00,
                ]);
                Log::info('Created new facility inventory item', [
                    'product' => $product->name,
                    'batch' => $batchNumber,
                    'quantity' => $row['quantity'],
                    'expiry_date' => $expiryDate
                ]);
            }

            return null;

        } catch (\Throwable $e) {
            Log::error('Facility inventory import error', [
                'error' => $e->getMessage(),
                'row' => $row,
                'trace' => $e->getTraceAsString()
            ]);
            return null;
        }
    }

    protected function getEligibleItem($itemName)
    {
        // Get current user's facility
        $facility = Facility::find($this->facilityId);
        if (!$facility) {
            Log::error('Facility not found', [
                'facility_id' => $this->facilityId,
                'import_id' => $this->importId
            ]);
            return null;
        }
        
        Log::info('Searching for eligible item', [
            'item_name' => $itemName,
            'facility_id' => $this->facilityId,
            'facility_type' => $facility->facility_type
        ]);
        
        // First try to find by product name directly for the specific facility type
        $eligibleItem = EligibleItem::whereHas('product', function($query) use ($itemName) {
            $query->where('name', $itemName);
        })->where('facility_type', $facility->facility_type)->first();
        
        if ($eligibleItem) {
            Log::info('Found eligible item (exact match)', [
                'item_name' => $itemName,
                'facility_type' => $facility->facility_type,
                'product_name' => $eligibleItem->product->name
            ]);
            return $eligibleItem;
        }
        
        // If not found, try to find by partial match for the specific facility type
        $eligibleItem = EligibleItem::whereHas('product', function($query) use ($itemName) {
            $query->where('name', 'LIKE', '%' . $itemName . '%');
        })->where('facility_type', $facility->facility_type)->first();
        
        if ($eligibleItem) {
            Log::info('Found eligible item (partial match)', [
                'item_name' => $itemName,
                'facility_type' => $facility->facility_type,
                'product_name' => $eligibleItem->product->name
            ]);
            return $eligibleItem;
        }
        
        // If still not found, try without facility type filter (fallback)
        $eligibleItem = EligibleItem::whereHas('product', function($query) use ($itemName) {
            $query->where('name', $itemName);
        })->first();
        
        if ($eligibleItem) {
            Log::warning('Item found but not eligible for this facility type', [
                'item' => $itemName,
                'user_facility_type' => $facility->facility_type,
                'eligible_for' => $eligibleItem->facility_type,
                'user_facility_id' => $facility->id
            ]);
        } else {
            Log::warning('Item not found in eligible items', [
                'item' => $itemName,
                'facility_type' => $facility->facility_type
            ]);
        }
        
        return $eligibleItem;
    }

    protected function getInventory($productId)
    {
        $inventory = FacilityInventory::where('product_id', $productId)
            ->where('facility_id', $this->facilityId)
            ->first();
            
        if (!$inventory) {
            $inventory = FacilityInventory::create([
                'facility_id' => $this->facilityId,
                'product_id' => $productId,
                'quantity' => 0,
            ]);
        }

        return $inventory;
    }

    protected function parseExpiryDate($expiryDateValue)
    {
        if (empty($expiryDateValue)) {
            return null;
        }

        try {
            // Check if it's an Excel serial number (numeric)
            if (is_numeric($expiryDateValue)) {
                $excelDate = (int) $expiryDateValue;
                $unixTimestamp = ($excelDate - 25569) * 86400;
                $date = Carbon::createFromTimestamp($unixTimestamp);
                return $date->format('Y-m-d');
            }

            // Try to parse as "M-y" format (Feb-25, Jun-27)
            try {
                $date = Carbon::createFromFormat('M-y', $expiryDateValue);
                return $date->startOfMonth()->format('Y-m-d');
            } catch (\Exception $e) {
                // Continue to next format
            }

            // Try various normal date formats
            $dateFormats = [
                'd-m-Y',    // 15-02-2025
                'Y-m-d',    // 2025-02-15
                'd/m/Y',    // 15/02/2025
                'Y/m/d',    // 2025/02/15
                'm-d-Y',    // 02-15-2025
                'Y-m-d H:i:s', // 2025-02-15 00:00:00
                'd-m-Y H:i:s', // 15-02-2025 00:00:00
            ];

            foreach ($dateFormats as $format) {
                try {
                    $date = Carbon::createFromFormat($format, $expiryDateValue);
                    return $date->format('Y-m-d');
                } catch (\Exception $e) {
                    continue;
                }
            }
            return null;

        } catch (\Exception $e) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function batchSize(): int
    {
        return 50;
    }

    public function registerEvents(): array
    {
        return [
            AfterImport::class => function (AfterImport $event) {                
                Cache::forget($this->importId);
                Log::info('Facility inventory import completed', ['import_id' => $this->importId]);
            },
        ];
    }
}
