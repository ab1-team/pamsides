<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BillPayment extends Model
{
    protected $fillable = [
        'bill_id',
        'amount_paid',
        'confirmed_by',
        'paid_at'
    ];

    public function bill()
    {
        return $this->belongsTo(MonthlyBill::class, 'bill_id');
    }
}
