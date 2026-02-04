<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class InventoryItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'inventory_id',
        'product_id',
        'warehouse_id',
        'quantity',
        'expiry_date',
        'batch_number',
        'barcode',
        'location',
        'notes',
        'uom',
        'unit_cost',
        'total_cost',
    ];

    public function inventory()
    {
        return $this->belongsTo(Inventory::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function location()
    {
        return $this->belongsTo(Location::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
