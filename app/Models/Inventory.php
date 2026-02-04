<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Product;
use App\Models\Warehouse;

class Inventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'quantity',
    ];

    public function items()
    {
        return $this->hasMany(InventoryItem::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

}
