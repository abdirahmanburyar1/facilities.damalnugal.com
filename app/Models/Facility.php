<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Facility extends Model
{

    protected $fillable = [
        'name',
        'email',
        'user_id',
        'district',
        'handled_by',
        'region',
        'phone',
        'address',
        'facility_type',
        'has_cold_storage',
        'is_active',
    ];

    public function inventories()
    {
        return $this->hasMany(FacilityInventory::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function handledby(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function eligibleProducts()
    {
        return $this->belongsToMany(Product::class, 'eligible_items', 'facility_type', 'product_id', 'facility_type', 'id')
            ->where('products.is_active', true)
            ->orderBy('products.name');
    }
    
}
