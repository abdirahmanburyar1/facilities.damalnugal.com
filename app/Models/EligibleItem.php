<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibleItem extends Model
{
    protected $table = 'eligible_items';
    protected $fillable = ['product_id', 'facility_id'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }
}

