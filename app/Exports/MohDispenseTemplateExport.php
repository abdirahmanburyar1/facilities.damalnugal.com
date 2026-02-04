<?php

namespace App\Exports;

use App\Models\FacilityInventoryItem;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

/**
 * Template for MOH dispense upload. Columns match moh_dispense_items only:
 * product (Item), batch_no, expiry_date, quantity, dispense_date, dispensed_by.
 * No source, location, warehouse, unit cost, or UOM.
 */
class MohDispenseTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    protected ?int $facilityId;

    public function __construct(?int $facilityId = null)
    {
        $this->facilityId = $facilityId;
    }

    public function array(): array
    {
        if ($this->facilityId === null) {
            return [];
        }

        $items = FacilityInventoryItem::query()
            ->select('product_id', 'batch_number', 'expiry_date')
            ->whereHas('inventory', fn ($q) => $q->where('facility_id', $this->facilityId))
            ->where('quantity', '>', 0)
            ->with('product:id,name')
            ->orderBy('product_id')
            ->orderBy('batch_number')
            ->get();

        $rows = [];
        foreach ($items as $item) {
            $productName = $item->product->name ?? (string) $item->product_id;
            $expiryDate = $item->expiry_date instanceof \DateTimeInterface
                ? Carbon::parse($item->expiry_date)->format('Y-m-d')
                : ($item->expiry_date ?? '');

            $rows[] = [
                $productName,
                (string) ($item->batch_number ?? ''),
                $expiryDate,
                '',  // quantity - user fills
                '',  // dispense_date - user fills
                '',  // dispensed_by - user fills
            ];
        }

        return $rows;
    }

    public function headings(): array
    {
        return ['Item', 'Batch No', 'Expiry Date', 'Quantity', 'Dispense Date', 'Dispensed By'];
    }
}
