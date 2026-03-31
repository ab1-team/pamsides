<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'customer_code',
        'initial_meter_reading',
        'meter_photo_url',
        'activated_at'
    ];

    public function ticket()
    {
        return $this->belongsTo(InstallationTicket::class, 'ticket_id');
    }

    public function bills()
    {
        return $this->hasMany(MonthlyBill::class, 'customer_id');
    }

    public function meterReadings()
    {
        return $this->hasMany(MeterReading::class, 'customer_id');
    }
}
