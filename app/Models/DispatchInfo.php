<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DispatchInfo extends Model
{
    protected $fillable = [
        'order_id',
        'transfer_id',
        'logistic_company_id',
        'driver_id',
        'dispatch_date',
        'no_of_cartoons',
        'received_cartons',
        'driver_number',
        'plate_number',
        'image',
    ];

    public function driver()
    {
        return $this->belongsTo(Driver::class);
    }
    public function logistic_company()
    {
        return $this->belongsTo(LogisticCompany::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
    public function transfer()
    {
        return $this->belongsTo(Transfer::class);
    }
}
