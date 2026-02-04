<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class LogisticCompany extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'email',
        'address',
        'incharge_person',
        'incharge_phone',
        'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function drivers(): HasMany
    {
        return $this->hasMany(Driver::class);
    }
} 