<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryAllocation extends Model
{
    protected $fillable = [
        'order_item_id',
        'transfer_item_id',
        'product_id',
        'warehouse_id',
        'location',
        'source',
        'batch_number',
        'expiry_date',
        'allocated_quantity',
        'received_quantity',
        'allocation_type',
        'updated_quantity',
        'transfer_reason',
        'unit_cost',
        'total_cost',
        'notes',
        'uom'
    ];

    public function product(){
        return $this->belongsTo(Product::class);
    }

    public function location(){
        return $this->belongsTo(Location::class);
    }

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }

    public function transfer_item(){
        return $this->belongsTo(TransferItem::class);
    }

    public function order_item(){
        return $this->belongsTo(OrderItem::class);
    }

    public function differences(){
        return $this->hasMany(PackingListDifference::class, 'inventory_allocation_id');
    }

    public function backorders(){
        return $this->hasManyThrough(BackOrder::class, PackingListDifference::class, 'inventory_allocation_id', 'id', 'id', 'back_order_id');
    }
}
