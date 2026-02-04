<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class FacilityInventoryMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'facility_id',
        'product_id',
        'movement_type', // 'facility_received' or 'facility_issued'
        'source_type', // 'transfer', 'order', 'dispense', 'moh_dispense'
        'source_id', // ID of the source record (transfer_id, order_id, dispence_id, moh_dispense_id)
        'source_item_id', // ID of the source item (transfer_item_id, order_item_id, dispence_item_id, moh_dispense_item_id)
        'facility_received_quantity',
        'facility_issued_quantity',
        'batch_number',
        'expiry_date',
        'barcode',
        'uom',
        'movement_date',
        'reference_number', // transfer number, order number, dispence number
        'notes',
        'created_by',
        'updated_by',
    ];

    // Relationships
    public function facility()
    {
        return $this->belongsTo(Facility::class);
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

    // Dynamic relationships based on source_type
    public function sourceRecord()
    {
        switch ($this->source_type) {
            case 'transfer':
                return $this->belongsTo(Transfer::class, 'source_id');
            case 'order':
                return $this->belongsTo(Order::class, 'source_id');
            case 'dispense':
                return $this->belongsTo(Dispence::class, 'source_id');
            case 'moh_dispense':
                return $this->belongsTo(MohDispense::class, 'source_id');
            default:
                return null;
        }
    }

    public function sourceItem()
    {
        switch ($this->source_type) {
            case 'transfer':
                return $this->belongsTo(TransferItem::class, 'source_item_id');
            case 'order':
                return $this->belongsTo(OrderItem::class, 'source_item_id');
            case 'dispense':
                return $this->belongsTo(DispenceItem::class, 'source_item_id');
            case 'moh_dispense':
                return $this->belongsTo(MohDispenseItem::class, 'source_item_id');
            default:
                return null;
        }
    }

    // Scopes
    public function scopeFacilityReceived($query)
    {
        return $query->where('movement_type', 'facility_received');
    }

    public function scopeFacilityIssued($query)
    {
        return $query->where('movement_type', 'facility_issued');
    }

    public function scopeByFacility($query, $facilityId)
    {
        return $query->where('facility_id', $facilityId);
    }

    public function scopeByProduct($query, $productId)
    {
        return $query->where('product_id', $productId);
    }

    public function scopeByDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('movement_date', [$startDate, $endDate]);
    }

    // Helper methods
    public static function recordFacilityReceived($data)
    {
        return self::create(array_merge($data, [
            'movement_type' => 'facility_received',
            'facility_issued_quantity' => 0,
            'created_by' => auth()->id(),
        ]));
    }

    public static function recordFacilityIssued($data)
    {
        return self::create(array_merge($data, [
            'movement_type' => 'facility_issued',
            'facility_received_quantity' => 0,
            'created_by' => auth()->id(),
        ]));
    }

    // Boot method to automatically set created_by and updated_by
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (auth()->check()) {
                $model->created_by = auth()->id();
            }
        });

        static::updating(function ($model) {
            if (auth()->check()) {
                $model->updated_by = auth()->id();
            }
        });
    }
}
