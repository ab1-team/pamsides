<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    protected $fillable = [
        'customer_id',
        'recorded_by',
        'reading_year',
        'reading_month',
        'meter_value',
        'photo_url',
        'recorded_at'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }
}
