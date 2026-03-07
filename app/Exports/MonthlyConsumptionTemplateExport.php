<?php

namespace App\Exports;

use App\Models\Product;
use App\Models\MonthlyConsumptionItem;
use App\Models\MonthlyConsumptionReport;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class MonthlyConsumptionTemplateExport implements FromArray, WithHeadings
{
    protected int $facilityId;
    protected int $year;

    public function __construct(int $facilityId, int $year)
    {
        $this->facilityId = $facilityId;
        $this->year = $year;
    }

    public function headings(): array
    {
        $headings = ['Item', 'Category', 'Dosage Form'];

        for ($m = 1; $m <= 12; $m++) {
            $label = now()->setDate($this->year, $m, 1)->format('M-y'); // e.g. Jan-26
            $headings[] = $label;
        }

        return $headings;
    }

    public function array(): array
    {
        // All reports for this facility/year
        $reports = MonthlyConsumptionReport::where('facility_id', $this->facilityId)
            ->where('month_year', 'like', $this->year . '-%')
            ->get();

        $reportIds = $reports->pluck('id');

        $items = MonthlyConsumptionItem::with(['report', 'product.category', 'product.dosage'])
            ->whereIn('parent_id', $reportIds)
            ->get();

        /** @var array<int,array<string,float>> $quantityMap [product_id][month_year] = qty */
        $quantityMap = [];
        foreach ($items as $item) {
            if (!$item->report) {
                continue;
            }
            $monthYear = $item->report->month_year;
            $quantityMap[$item->product_id][$monthYear] = (float) $item->quantity;
        }

        // Products eligible for this facility (by facility_type via Product->eligible relation)
        $eligibleProducts = Product::with(['category', 'dosage'])
            ->whereHas('eligible', function ($q) {
                $q->where('facility_type', optional(auth()->user()->facility)->facility_type);
            })
            ->where('is_active', true)
            ->get();

        $rows = [];

        foreach ($eligibleProducts as $product) {
            $row = [
                $product->name,
                optional($product->category)->name,
                optional($product->dosage)->name,
            ];

            for ($m = 1; $m <= 12; $m++) {
                $monthYear = sprintf('%04d-%02d', $this->year, $m);
                $qty = $quantityMap[$product->id][$monthYear] ?? null;
                $row[] = $qty === null ? '' : (float) $qty;
            }

            $rows[] = $row;
        }

        return $rows;
    }
}

