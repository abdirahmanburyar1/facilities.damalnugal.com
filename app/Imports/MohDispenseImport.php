<?php

namespace App\Imports;

use App\Models\MohDispenseItem;
use App\Models\Product;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Carbon\Carbon;

class MohDispenseImport implements ToModel, WithHeadingRow, WithValidation, WithChunkReading
{
    protected $mohDispenseId;

    public function __construct($mohDispenseId)
    {
        $this->mohDispenseId = $mohDispenseId;
    }

    public function chunkSize(): int
    {
        return 100;
    }

    public function model(array $row)
    {
        // Find product by name, id, or productID
        $product = Product::where('name', $row['item'])
            ->orWhere('id', $row['item'])
            ->orWhere('productID', $row['item'])
            ->first();

        if (!$product) {
            throw new \Exception("Product not found: " . $row['item']);
        }

        return new MohDispenseItem([
            'moh_dispense_id' => $this->mohDispenseId,
            'product_id' => $product->id,
            'batch_no' => (string) ($row['batch_no'] ?? ''),
            'expiry_date' => $this->parseDate($row['expiry_date']),
            'quantity' => (int) $row['quantity'],
            'dispense_date' => $this->parseDate($row['dispense_date']),
            'dispensed_by' => $row['dispensed_by'] ?? '',
        ]);
    }

    public function rules(): array
    {
        return [
            'item' => 'required|string',
            'batch_no' => 'required',
            'expiry_date' => 'required',
            'quantity' => 'required|integer|min:1',
            'dispense_date' => 'required',
            'dispensed_by' => 'required|string|max:255',
        ];
    }

    private function parseDate($date)
    {
        if (empty($date)) {
            throw new \Exception("Date field is required");
        }

        // Handle Excel date serial numbers
        if (is_numeric($date)) {
            try {
                return Carbon::createFromFormat('Y-m-d', \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject($date)->format('Y-m-d'));
            } catch (\Exception $e) {
                return Carbon::createFromFormat('Y-m-d', gmdate('Y-m-d', ($date - 25569) * 86400));
            }
        }

        // Handle string dates
        $dateString = trim($date);
        $formats = ['Y-m-d', 'd/m/Y', 'm/d/Y', 'd-m-Y', 'm-d-Y'];

        foreach ($formats as $format) {
            try {
                $parsedDate = Carbon::createFromFormat($format, $dateString);
                if ($parsedDate) {
                    return $parsedDate;
                }
            } catch (\Exception $e) {
                continue;
            }
        }

        // Try Carbon's flexible parsing
        try {
            return Carbon::parse($dateString);
        } catch (\Exception $e) {
            throw new \Exception("Unable to parse date: {$date}. Please use format YYYY-MM-DD");
        }
    }
}