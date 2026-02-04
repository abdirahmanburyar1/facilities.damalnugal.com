<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;

class FacilityReorderLevelsTemplateExport implements FromArray, WithHeadings, ShouldAutoSize
{
    /** @var array<int, array<int, string>> */
    protected array $rows;

    /**
     * @param array<int, array<int, string>> $rows
     */
    public function __construct(array $rows)
    {
        $this->rows = $rows;
    }

    public function array(): array
    {
        return $this->rows;
    }

    public function headings(): array
    {
        return ['Item description', 'AMC', 'Lead time'];
    }
}


