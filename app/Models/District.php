<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class District extends Model
{
    use HasFactory;
    
    protected $fillable = [
        'name',
        'region',
        // 'state_id'
    ];
    
    /**
     * Get the state that owns the district.
     */
    // public function state()
    // {
    //     return $this->belongsTo(State::class);
    // }
    
    // /**
    //  * Get the cities for the district.
    //  */
    // public function cities()
    // {
    //     return $this->hasMany(City::class);
    // }
} 