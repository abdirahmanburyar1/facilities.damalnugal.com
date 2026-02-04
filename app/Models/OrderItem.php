<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_id',
        'product_id',
        'quantity',
        'warehouse_id',
        'quantity_on_order',
        'amc',
        'soh',
        'quantity_to_release',
        'received_quantity',
        'no_of_days',
    ];

    public function inventory_allocations(){
        return $this->hasMany(InventoryAllocation::class, 'order_item_id');
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
    
    /**
     * Get the differences for this order item through inventory allocations
     */
    public function differences()
    {
        return $this->hasManyThrough(PackingListDifference::class, InventoryAllocation::class, 'order_item_id', 'inventory_allocation_id');
    }

    /**
     * Get the back orders for this order item through the parent order
     */
    public function backorders()
    {
        return $this->order->backorders();
    }
}
