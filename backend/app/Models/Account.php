<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    protected $fillable = [
        'parent_id',
        'lev1',
        'lev2',
        'lev3',
        'lev4',
        'kode_akun',
        'nama_akun',
        'jenis_mutasi',
        'tgl_nonaktif',
    ];

    protected $casts = [
        'lev1' => 'integer',
        'lev2' => 'integer',
        'lev3' => 'integer',
        'lev4' => 'integer',
        'tgl_nonaktif' => 'date',
    ];

    public function akunLevel1(): BelongsTo
    {
        return $this->belongsTo(AkunLevel1::class, 'lev1', 'lev1');
    }

    public function akunLevel2(): BelongsTo
    {
        return $this->belongsTo(AkunLevel2::class, 'lev2', 'lev2');
    }

    public function akunLevel3(): BelongsTo
    {
        return $this->belongsTo(AkunLevel3::class, 'lev3', 'lev3');
    }
}
