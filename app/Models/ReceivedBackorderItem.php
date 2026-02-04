<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReceivedBackorderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'received_backorder_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'barcode',
        'expire_date',
        'batch_number',
        'warehouse_id',
        'uom',
        'location',
        'note'
    ];

    protected $casts = [
        'expire_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
    ];

    /**
     * Get the received backorder that owns the item
     */
    public function receivedBackorder(): BelongsTo
    {
        return $this->belongsTo(ReceivedBackorder::class);
    }

    /**
     * Get the product associated with this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
