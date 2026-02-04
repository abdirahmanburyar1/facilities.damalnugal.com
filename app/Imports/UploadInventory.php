<?php

namespace App\Imports;

use App\Models\EligibleItem;
use App\Models\Facility;
use App\Models\FacilityInventory;
use App\Models\FacilityInventoryItem;
use App\Models\Product;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class UploadInventory implements
    ToModel,
    WithHeadingRow,
    WithChunkReading,
    WithBatchInserts,
    SkipsEmptyRows
{
    public int $createdCount = 0;
    public int $missingProductRows = 0;
    /** @var array<string,true> */
    private array $missingProductsSet = [];
    /** @var string[] */
    public array $missingProductsSample = [];

    private int $facilityId;
    private ?string $facilityType = null;

    public function __construct(int $facilityId)
    {
        $this->facilityId = $facilityId;

        try {
            $facility = Facility::find($facilityId);
            $this->facilityType = $facility?->facility_type;
        } catch (\Throwable $e) {
            $this->facilityType = null;
        }
    }

    public function model(array $row)
    {
        try {
            // Required fields (support multiple header variations)
            $itemName = $row['item'] ?? $row['Item'] ?? null;
            $quantityRaw = $row['quantity'] ?? $row['Quantity'] ?? null;
            $batchNumber = $row['batch_no'] ?? $row['Batch No'] ?? $row['BATCH_NO'] ?? $row['batch_number'] ?? null;
            $expiryDateValue = $row['expiry_date'] ?? $row['Expiry Date'] ?? $row['EXPIRY_DATE'] ?? $row['expiry'] ?? null;

            // Quantity may be "0" which empty() treats as falsey; allow 0.
            if (empty($itemName) || $quantityRaw === null || $quantityRaw === '' || empty($batchNumber) || empty($expiryDateValue)) {
                return null;
            }

            $product = $this->getEligibleProduct((string) $itemName);
            if (!$product) {
                return null;
            }

            $inventory = $this->getFacilityInventory($product->id);

            $expiryDate = $this->parseExpiryDate($expiryDateValue);
            $batchNumber = trim((string) $batchNumber);

            $rowQuantity = (float) $quantityRaw;
            if ($rowQuantity < 0) {
                return null;
            }

            $uom = trim((string) ($row['uom'] ?? $row['UoM'] ?? $row['UOM'] ?? '')) ?: null;
            $barcode = trim((string) ($row['barcode'] ?? $row['Barcode'] ?? $row['BARCODE'] ?? '')) ?: null;

            $unitCostRaw = $row['unit_cost'] ?? $row['Unit Cost'] ?? $row['UNIT_COST'] ?? $row['UnitCost'] ?? null;
            $unitCost = ($unitCostRaw !== null && $unitCostRaw !== '') ? (float) $unitCostRaw : 0.0;
            $totalCost = $unitCost * $rowQuantity;

            DB::transaction(function () use ($inventory, $product, $batchNumber, $expiryDate, $rowQuantity, $uom, $barcode, $unitCost, $totalCost) {
                $existingItem = FacilityInventoryItem::where('facility_inventory_id', $inventory->id)
                    ->where('product_id', $product->id)
                    ->where('batch_number', $batchNumber)
                    ->whereDate('expiry_date', $expiryDate)
                    ->first();

                if ($existingItem) {
                    $existingItem->increment('quantity', (int) round($rowQuantity));

                    // Best-effort metadata fill/update
                    if (empty($existingItem->uom) && !empty($uom)) {
                        $existingItem->uom = $uom;
                    }
                    if (empty($existingItem->barcode) && !empty($barcode)) {
                        $existingItem->barcode = $barcode;
                    }
                    if (!empty($unitCost) && (empty($existingItem->unit_cost) || (float) $existingItem->unit_cost <= 0)) {
                        $existingItem->unit_cost = $unitCost;
                    }
                    if (!empty($totalCost)) {
                        $existingItem->total_cost = ((float) ($existingItem->total_cost ?? 0)) + $totalCost;
                    }
                    $existingItem->save();
                } else {
                    FacilityInventoryItem::create([
                        'facility_inventory_id' => $inventory->id,
                        'product_id' => $product->id,
                        'quantity' => (int) round($rowQuantity),
                        'expiry_date' => $expiryDate,
                        'batch_number' => $batchNumber,
                        'barcode' => $barcode,
                        'uom' => $uom,
                        'unit_cost' => $unitCost,
                        'total_cost' => $totalCost,
                    ]);
                }

                $inventory->increment('quantity', (int) round($rowQuantity));
                $this->createdCount++;
            });

            return null;
        } catch (\Throwable $e) {
            Log::error('Facility inventory import error', [
                'error' => $e->getMessage(),
                'row' => $row,
            ]);

            // Bubble up 422-style errors if we ever add them
            if ((int) $e->getCode() === 422) {
                throw $e;
            }

            return null;
        }
    }

    private function getEligibleProduct(string $itemName): ?Product
    {
        $cleanName = trim(preg_replace('/\s+/', ' ', $itemName));
        if ($cleanName === '') return null;

        // Prefer exact match
        $product = Product::where('name', $cleanName)->first();
        if (!$product) {
            $product = Product::where('name', 'LIKE', '%' . $cleanName . '%')->first();
        }

        if (!$product) {
            $this->recordMissingProduct($cleanName);
            return null;
        }

        // If facility type is known, enforce eligibility
        if ($this->facilityType) {
            $eligible = EligibleItem::where('product_id', $product->id)
                ->where('facility_type', $this->facilityType)
                ->exists();
            if (!$eligible) {
                // Treat as "missing" so user understands why it didn't import
                $this->recordMissingProduct($cleanName);
                return null;
            }
        }

        return $product;
    }

    private function recordMissingProduct(string $itemName): void
    {
        $this->missingProductRows++;

        if (!isset($this->missingProductsSet[$itemName])) {
            $this->missingProductsSet[$itemName] = true;
            if (count($this->missingProductsSample) < 25) {
                $this->missingProductsSample[] = $itemName;
            }
        }
    }

    private function getFacilityInventory(int $productId): FacilityInventory
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

    private function parseExpiryDate($expiryDateValue): ?string
    {
        if ($expiryDateValue === null || $expiryDateValue === '') {
            return null;
        }

        try {
            // Excel serial number
            if (is_numeric($expiryDateValue)) {
                $excelDate = (int) $expiryDateValue;
                $unixTimestamp = ($excelDate - 25569) * 86400;
                return Carbon::createFromTimestamp($unixTimestamp)->format('Y-m-d');
            }

            $value = trim((string) $expiryDateValue);

            // Try M-y (Feb-25)
            try {
                $date = Carbon::createFromFormat('M-y', $value);
                return $date->startOfMonth()->format('Y-m-d');
            } catch (\Throwable $e) {
                // continue
            }

            $formats = [
                'd-m-Y',
                'Y-m-d',
                'd/m/Y',
                'Y/m/d',
                'm-d-Y',
                'Y-m-d H:i:s',
                'd-m-Y H:i:s',
            ];

            foreach ($formats as $format) {
                try {
                    return Carbon::createFromFormat($format, $value)->format('Y-m-d');
                } catch (\Throwable $e) {
                    // continue
                }
            }

            // Last resort parse
            return Carbon::parse($value)->format('Y-m-d');
        } catch (\Throwable $e) {
            return null;
        }
    }

    public function chunkSize(): int
    {
        return 200;
    }

    public function batchSize(): int
    {
        return 200;
    }
}

