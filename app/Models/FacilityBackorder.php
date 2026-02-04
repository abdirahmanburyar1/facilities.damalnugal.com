<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FacilityBackorder extends Model
{
    protected $table = 'facility_backorders';
    protected $fillable = [
        'order_item_id',
        'transfer_item_id',
        'product_id',
        'inventory_allocation_id',
        'type',
        'quantity',
        'notes',
        'status',
        'finalized',
        'created_by',
        'updated_by',
        'reviewed_by',
        'reviewed_at',
        'rejected_by',
        'rejected_at',
        'approved_by',
        'approved_at',
    ];

    public function orderItem()
    {
        return $this->belongsTo(OrderItem::class, 'order_item_id');
    }
    
    public function transferItem()
    {
        return $this->belongsTo(TransferItem::class, 'transfer_item_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function reviewedBy()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function rejectedBy()
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }
    
    /**
     * Get the inventory allocation associated with this backorder
     */
    public function inventoryAllocation()
    {
        return $this->belongsTo(InventoryAllocation::class, 'inventory_allocation_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
