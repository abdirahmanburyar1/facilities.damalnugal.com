<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MonthlyConsumptionItem extends Model
{
    use HasFactory;

    protected $table = 'monthly_consumption_items';

    protected $fillable = [
        'parent_id',
        'product_id',
        'quantity',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(MonthlyConsumptionReport::class, 'parent_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}