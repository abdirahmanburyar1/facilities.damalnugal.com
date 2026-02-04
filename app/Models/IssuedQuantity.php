<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IssuedQuantity extends Model
{
    protected $fillable = [
        'product_id',
        'warehouse_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'issued_date',
        'barcode',
        'batch_number',
        'uom',
        'expiry_date',
        'issued_by'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    public function issuer()
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
} 