<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class JenisLaporan extends Model
{
    use HasFactory;
    protected $table = 'jenis_laporans';

    protected $fillable = [
        'urut',
        'nama_laporan',
        'file',
        'paper_size',
        'orientation',
        'awal_tahun',
    ];

    public function subLaporans()
    {
        return $this->hasMany(SubLaporan::class, 'id_lap', 'id');
    }

}
