<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class SubLaporan extends Model
{
    use HasFactory;

    protected $table = 'sub_laporans'; 

    protected $guarded = ['id'];

    /**
     * Relasi balik ke Laporan Utama
     */
    public function jenisLaporan()
    {
        return $this->belongsTo(JenisLaporan::class, 'id_lap', 'id');
    }
}
