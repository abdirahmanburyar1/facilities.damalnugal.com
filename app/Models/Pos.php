<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pos extends Model
{
    protected $fillable = [
        'product_id',
        'facility_id',
        'dose',
        'units',
        'frequency',
        'route',
        'start_date',
        'duration',
        'total_quantity',
        'patient_name',
        'patient_phone',
        'created_by',
        'updated_by',
        'pos_date'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }
}
