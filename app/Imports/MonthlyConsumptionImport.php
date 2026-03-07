<?php

namespace App\Imports;

use App\Models\Product;
use App\Models\MonthlyConsumptionReport;
use App\Models\MonthlyConsumptionItem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\SkipsEmptyRows;

class MonthlyConsumptionImport implements
    ToCollection,
    WithHeadingRow,
    WithChunkReading,
    WithBatchInserts,
    SkipsEmptyRows
{
    public int $facilityId;
    public ?int $userId;

    /** @var array<string,string> */
    protected array $monthColumns = []; // heading_key => YYYY-MM

    public int $processedRows = 0;
    public int $updatedCount = 0;
    public int $createdReports = 0;
    public array $missingProductsSample = [];

    /** @var array<string,int> */
    protected array $reportIdCache = []; // month_year => report_id

    public function __construct(int $facilityId, ?int $userId = null)
    {
        $this->facilityId = $facilityId;
        $this->userId = $userId;
    }

    public function collection(Collection $rows)
    {
        if ($rows->isEmpty()) {
            return;
        }

        if (empty($this->monthColumns)) {
            $this->detectMonthColumns($rows->first());
        }

        foreach ($rows as $row) {
            $this->processRow($row);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }

    public function batchSize(): int
    {
        return 200;
    }

    protected function detectMonthColumns($row): void
    {
        $array = is_array($row) ? $row : $row->toArray();

        foreach ($array as $key => $value) {
            if ($key === null) {
                continue;
            }

            $normalizedKey = trim(strtolower($key));
            if (in_array($normalizedKey, ['item', 'items', 'product', 'category', 'dosage_form', 'dosage', 'uom'], true)) {
                continue;
            }

            $monthYear = $this->parseMonthKey($normalizedKey);
            if ($monthYear) {
                $this->monthColumns[$key] = $monthYear;
            }
        }
    }

    protected function parseMonthKey(string $key): ?string
    {
        // Normalise separators to a single space
        $clean = str_replace(['_', '-', '/'], ' ', $key);
        $clean = preg_replace('/\s+/', ' ', $clean ?? '') ?? '';
        $clean = trim($clean);

        if ($clean === '') {
            return null;
        }

        $monthMap = [
            'jan' => 1, 'feb' => 2, 'mar' => 3, 'apr' => 4, 'may' => 5, 'jun' => 6,
            'jul' => 7, 'aug' => 8, 'sep' => 9, 'oct' => 10, 'nov' => 11, 'dec' => 12,
        ];

        // Helper to normalise 2‑digit years like "25" → 2025
        $normalizeYear = static function (int $year): int {
            if ($year < 100) {
                // Assume 2000‑2099 for 2‑digit years
                return 2000 + $year;
            }
            return $year;
        };

        // Formats like "jan 2025", "january 2025", "jan 25"
        if (preg_match('/^([a-z]+)\s+(\d{2}|\d{4})$/i', $clean, $m)) {
            $monthName = strtolower(substr($m[1], 0, 3));
            if (!isset($monthMap[$monthName])) {
                return null;
            }
            $year = $normalizeYear((int) $m[2]);
            return sprintf('%04d-%02d', $year, $monthMap[$monthName]);
        }

        // Formats like "2025 01" or "2025 1"
        if (preg_match('/^(\d{4})\s+(\d{1,2})$/', $clean, $m)) {
            $year = (int) $m[1];
            $month = (int) $m[2];
            if ($month < 1 || $month > 12) {
                return null;
            }
            return sprintf('%04d-%02d', $year, $month);
        }

        return null;
    }

    protected function processRow($row): void
    {
        $this->processedRows++;

        $data = is_array($row) ? $row : $row->toArray();

        $itemName = trim((string) ($data['item'] ?? $data['items'] ?? $data['product'] ?? ''));
        if ($itemName === '') {
            return;
        }

        $product = Product::where('name', $itemName)->first();
        if (!$product) {
            if (count($this->missingProductsSample) < 10 && !in_array($itemName, $this->missingProductsSample, true)) {
                $this->missingProductsSample[] = $itemName;
            }
            return;
        }

        foreach ($this->monthColumns as $columnKey => $monthYear) {
            if (!array_key_exists($columnKey, $data)) {
                continue;
            }
            $raw = $data[$columnKey];

            if ($raw === null || $raw === '') {
                // Empty quantity: ignore, keep existing value
                continue;
            }

            if (!is_numeric($raw)) {
                continue;
            }

            $qty = (float) $raw;

            $reportId = $this->getOrCreateReportId($monthYear);

            $item = MonthlyConsumptionItem::where('parent_id', $reportId)
                ->where('product_id', $product->id)
                ->first();

            if ($item) {
                $item->quantity = $qty;
                $item->save();
            } else {
                MonthlyConsumptionItem::create([
                    'parent_id' => $reportId,
                    'product_id' => $product->id,
                    'quantity' => $qty,
                ]);
            }

            $this->updatedCount++;
        }
    }

    protected function getOrCreateReportId(string $monthYear): int
    {
        if (isset($this->reportIdCache[$monthYear])) {
            return $this->reportIdCache[$monthYear];
        }

        $report = MonthlyConsumptionReport::firstOrCreate(
            [
                'facility_id' => $this->facilityId,
                'month_year' => $monthYear,
            ],
            [
                'generated_by' => $this->userId,
            ]
        );

        if ($report->wasRecentlyCreated) {
            $this->createdReports++;
        }

        $this->reportIdCache[$monthYear] = $report->id;

        return $report->id;
    }
}

