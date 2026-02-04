<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Location extends Model
{
    protected $fillable = ['location','warehouse_id'];

    public function warehouse(){
        return $this->belongsTo(Warehouse::class);
    }
}
