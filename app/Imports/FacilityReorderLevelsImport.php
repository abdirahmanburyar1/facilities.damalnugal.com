<?php

namespace App\Imports;

use App\Models\FacilityReorderLevel;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Illuminate\Support\Collection;

class FacilityReorderLevelsImport implements ToCollection, WithHeadingRow
{
    protected int $facilityId;
    /** @var array<string,int> */
    protected array $nameToProductId;

    public int $created = 0;
    public int $updated = 0;
    public int $skipped = 0;

    /**
     * @param array<string,int> $nameToProductId Lowercased product name => product_id
     */
    public function __construct(int $facilityId, array $nameToProductId)
    {
        $this->facilityId = $facilityId;
        $this->nameToProductId = $nameToProductId;
    }

    public function collection(Collection $rows)
    {
        foreach ($rows as $row) {
            $name = trim((string)($row['item_description'] ?? ''));
            if ($name === '') {
                $this->skipped++;
                continue;
            }

            $productId = $this->nameToProductId[strtolower($name)] ?? null;
            if (!$productId) {
                $this->skipped++;
                continue;
            }

            $amc = is_numeric($row['amc'] ?? null) ? (float)$row['amc'] : 0.0;
            $lead = (int)($row['lead_time'] ?? 0);
            if ($lead < 1) { $lead = 1; }

            $record = FacilityReorderLevel::updateOrCreate(
                [
                    'facility_id' => $this->facilityId,
                    'product_id' => $productId,
                ],
                [
                    'amc' => $amc,
                    'lead_time' => $lead,
                ]
            );

            $record->wasRecentlyCreated ? $this->created++ : $this->updated++;
        }
    }
}


