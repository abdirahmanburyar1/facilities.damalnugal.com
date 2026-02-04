<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EligibleItem extends Model
{
    protected $table = 'eligible_items';
    protected $fillable = ['product_id', 'facility_type'];

    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    // No facility relationship needed since we use facility_type string
}
