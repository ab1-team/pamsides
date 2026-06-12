<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'nama',
        'alamat',
        'email',
        'telepon',
        'domain',
        'status_pembayaran',
        'batas_tagihan',
        'toleransi_tunggakan',
        'logo',
        'pesan_tagihan',
        'pesan_pembayaran',
    ];

    protected $casts = [
        'status_pembayaran' => 'boolean',
        'batas_tagihan' => 'integer',
        'toleransi_tunggakan' => 'integer',
    ];

    public function villages()
    {
        return $this->hasMany(Village::class, 'setting_id');
    }
}
