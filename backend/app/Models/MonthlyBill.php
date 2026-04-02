<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MonthlyBill extends Model
{
    protected $fillable = [
        'customer_id',
        'billing_period_year',
        'billing_period_month',
        'meter_reading_start',
        'meter_reading_end',
        'usage_m3',
        'usage_charge',
        'abodemen',
        'penalty_amount',
        'total_amount',
        'status',
        'due_date'
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    public function billPayments()
    {
        return $this->hasMany(BillPayment::class, 'bill_id');
    }
}
