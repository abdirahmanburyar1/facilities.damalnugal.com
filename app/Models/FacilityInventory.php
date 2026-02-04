<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityInventory extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'facility_id',
        'quantity',
    ];

    
    public function items()
    {
        return $this->hasMany(FacilityInventoryItem::class, 'facility_inventory_id');
    }
    
    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
}
