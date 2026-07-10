<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Amount extends Model
{
    protected $table = 'amount';

    public $timestamps = false;

    protected $fillable = [
        'account_id',
        'bulan',
        'tahun',
        'debit',
        'kredit',
    ];

    protected $casts = [
        'debit' => 'float',
        'kredit' => 'float',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
