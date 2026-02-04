<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispenceItem extends Model
{
    protected $fillable = [
        'dispence_id',
        'product_id',
        'dose',
        'frequency',
        'expiry_date',
        'batch_number',
        'barcode',
        'uom',
        'duration',
        'quantity',
        'created_by',
        'updated_by',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->created_by = auth()->id();
        });

        static::updating(function ($model) {
            $model->updated_by = auth()->id();
        });
    }

    public function dispence()
    {
        return $this->belongsTo(Dispence::class);
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
}
