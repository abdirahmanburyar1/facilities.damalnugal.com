<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class FacilityInventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_inventory_id',
        'product_id',
        'quantity',
        'expiry_date',
        'batch_number',
        'barcode',
        'notes',
        'uom',
        'unit_cost',
        'total_cost',
    ];

    public function inventory()
    {
        return $this->belongsTo(FacilityInventory::class, 'facility_inventory_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
