<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DisposalItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'disposal_id',
        'product_id',
        'quantity',
        'unit_cost',
        'total_cost',
        'barcode',
        'expire_date',
        'batch_number',
        'uom',
        'location',
        'facility',
        'warehouse',
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
     * Get the disposal that owns the item
     */
    public function disposal(): BelongsTo
    {
        return $this->belongsTo(Disposal::class);
    }

    /**
     * Get the product associated with this item
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
