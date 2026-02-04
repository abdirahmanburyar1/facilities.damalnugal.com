<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MohDispenseItem extends Model
{
    protected $fillable = [
        'moh_dispense_id',
        'product_id',
        'batch_no',
        'expiry_date',
        'quantity',
        'dispense_date',
        'dispensed_by',
    ];

    protected $casts = [
        'expiry_date' => 'date',
        'dispense_date' => 'date',
    ];

    public function mohDispense(): BelongsTo
    {
        return $this->belongsTo(MohDispense::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

}