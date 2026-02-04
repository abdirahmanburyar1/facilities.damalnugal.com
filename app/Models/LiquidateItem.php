<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LiquidateItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'liquidate_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'barcode',
        'expire_date',
        'batch_number',
        'uom',
        'location',
        'note',
        'type',
        'attachments'
    ];

    protected $casts = [
        'expire_date' => 'date',
        'unit_cost' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'attachments' => 'array',
    ];

    /**
     * Get the liquidate that owns the item
     */
    public function liquidate(): BelongsTo
    {
        return $this->belongsTo(Liquidate::class);
    }

    /**
     * Get the product associated with this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
