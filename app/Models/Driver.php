<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Driver extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'driver_id',
        'name',
        'logistic_company_id',
        'phone',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(LogisticCompany::class, 'logistic_company_id');
    }
} 