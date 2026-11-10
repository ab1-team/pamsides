<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeterReset extends Model
{
    protected $fillable = [
        'customer_id',
        'period_year',
        'period_month',
        'previous_meter_value',
        'old_meter_final',
        'new_meter_start',
        'reason',
        'created_by',
    ];

    protected $casts = [
        'previous_meter_value' => 'float',
        'old_meter_final' => 'float',
        'new_meter_start' => 'float',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function bills()
    {
        return $this->hasMany(MonthlyBill::class, 'meter_reset_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
