<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\Role;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Approval extends Model
{
    use HasFactory;
    protected $fillable = [
        'role_id',
        'activity_type',
        'approval_level',
        'is_active',
        'description',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'approval_level' => 'integer',
    ];

    /**
     * Get the role that owns the approval.
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }
}
