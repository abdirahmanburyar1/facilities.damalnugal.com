<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Warehouse extends Model
{
    use HasFactory, SoftDeletes;
    
    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'code',
        'address',
        'state',
        'district',
        'city',
        'state_id',
        'district_id',
        'city_id',
        'manager_name',
        'manager_phone',
        'manager_email',
        'status',
        'user_id',
    ];    

    /**
     * Get the users associated with the warehouse.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    /**
     * Get the state associated with the warehouse.
     */
    public function state()
    {
        return $this->belongsTo(State::class);
    }
    
    /**
     * Get the district associated with the warehouse.
     */
    public function district()
    {
        return $this->belongsTo(District::class);
    }
    
    /**
     * Get the city associated with the warehouse.
     */
    public function city()
    {
        return $this->belongsTo(City::class);
    }
}
