<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PackingListDifference extends Model
{
    use HasFactory;

    protected $table = 'packing_list_differences';

    protected $fillable = [
        'packing_listitem_id', // Keep for warehouse compatibility
        'inventory_allocation_id', // Main field for facilities (replaces order_item_id and transfer_item_id)
        'back_order_id',
        'product_id',
        'quantity',
        'finalized',
        'status',
        'notes'
    ];

    public function orderItem()
    {
        return $this->hasOneThrough(OrderItem::class, InventoryAllocation::class, 'id', 'id', 'inventory_allocation_id', 'order_item_id');
    }

    public function transferItem()
    {
        return $this->hasOneThrough(TransferItem::class, InventoryAllocation::class, 'id', 'id', 'inventory_allocation_id', 'transfer_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function backOrder()
    {
        return $this->belongsTo(BackOrder::class);
    }

    public function inventoryAllocation()
    {
        return $this->belongsTo(InventoryAllocation::class, 'inventory_allocation_id');
    }
} 