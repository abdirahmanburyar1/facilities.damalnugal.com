<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacilityReorderLevel extends Model
{
    use HasFactory;

    protected $table = 'facility_reorder_levels';

    protected $fillable = [
        'facility_id',
        'product_id',
        'amc',
        'lead_time',
        'reorder_level',
    ];

    protected $casts = [
        'amc' => 'decimal:2',
        'lead_time' => 'integer',
        'reorder_level' => 'decimal:2',
    ];

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function calculateReorderLevel(): float
    {
        $this->reorder_level = (float) $this->amc * (int) $this->lead_time;
        return (float) $this->reorder_level;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::saving(function (FacilityReorderLevel $model) {
            $model->calculateReorderLevel();
        });
    }
}


