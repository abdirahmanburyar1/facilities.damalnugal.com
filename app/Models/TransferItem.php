<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TransferItem extends Model
{
    protected $fillable = [
        'transfer_id',
        'product_id',
        'quantity',
        'quantity_to_release',
        'quantity_per_unit'
    ];
    
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class);
    }
    
    public function inventory_allocations(){
        return $this->hasMany(InventoryAllocation::class, 'transfer_item_id');
    }

    /**
     * Get the differences for this transfer item through inventory allocations
     */
    public function differences()
    {
        return $this->hasManyThrough(PackingListDifference::class, InventoryAllocation::class, 'transfer_item_id', 'inventory_allocation_id');
    }

}
