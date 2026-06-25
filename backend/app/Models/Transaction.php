<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Transaction extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'tgl_transaksi',
        'account_debet',
        'account_kredit',
        'transaction_group',
        'reverence_type',
        'reverence_id',
        'keterangan_transaksi',
        'relasi',
        'saldo',
        'urutan',
        'id_user',
    ];

    protected $casts = [
        'tgl_transaksi' => 'date',
        'saldo'         => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'id_user');
    }

    public function accountDebet()
    {
        return $this->belongsTo(Account::class, 'account_debet', 'kode_akun');
    }

    public function accountKredit()
    {
        return $this->belongsTo(Account::class, 'account_kredit', 'kode_akun');
    }

    public function reverence()
    {
        if (! $this->reverence_type || ! $this->reverence_id) {
            return null;
        }

        $known = [
            'payment'      => \App\Models\Payment::class,
            'monthly_bill' => \App\Models\MonthlyBill::class,
            'customer'     => \App\Models\Customer::class,
        ];

        $type  = $this->reverence_type;
        $class = \Illuminate\Database\Eloquent\Relations\Relation::getMorphedModel($type) ?? ($known[$type] ?? null);

        if (! $class || ! class_exists($class)) {
            return null;
        }

        return $class::find($this->reverence_id);
    }
}
