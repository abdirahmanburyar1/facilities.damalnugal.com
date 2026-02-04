<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TrustedDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'device_token',
        'device_name',
        'ip_address',
        'last_used_at',
    ];

    /**
     * Get the user that owns the trusted device.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
} 