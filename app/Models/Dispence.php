<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Dispence extends Model
{
    protected $fillable = [
        'dispence_number',
        'dispence_date',
        'patient_name',
        'patient_age',
        'patient_gender',
        'patient_phone',
        'facility_id',
        'diagnosis',
        'dispenced_by',
    ];

    public function facility()
    {
        return $this->belongsTo(Facility::class);
    }

    public function dispenced_by()
    {
        return $this->belongsTo(User::class, 'dispenced_by');
    }

    public function items()
    {
        return $this->hasMany(DispenceItem::class);
    }

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($dispence) {
            $lastDispence = self::latest('dispence_number')->first();
            $number = $lastDispence ? (int)substr($lastDispence->dispence_number, 8) + 1 : 1;
            $dispence->dispence_number = 'DISP-' . date('Ymd') . '-' . str_pad($number, 5, '0', STR_PAD_LEFT);
        });
    }
}
