<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MohDispense extends Model
{
    protected $fillable = [
        'moh_dispense_number',
        'facility_id',
        'created_by',
        'status',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($mohDispense) {
            if (empty($mohDispense->moh_dispense_number)) {
                $mohDispense->moh_dispense_number = self::generateDispenseNumber();
            }
        });
    }

    public static function generateDispenseNumber()
    {
        $date = now()->format('Ymd');
        $lastNumber = self::whereDate('created_at', today())
            ->where('moh_dispense_number', 'like', "MOH-DISP-{$date}-%")
            ->orderBy('moh_dispense_number', 'desc')
            ->value('moh_dispense_number');

        if ($lastNumber) {
            $sequence = (int) substr($lastNumber, -5) + 1;
        } else {
            $sequence = 1;
        }

        return 'MOH-DISP-' . $date . '-' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(Facility::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(MohDispenseItem::class);
    }

}