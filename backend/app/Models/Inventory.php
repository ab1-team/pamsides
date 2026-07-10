<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Inventory extends Model
{
    protected $table = 'inventories';

    protected $fillable = [
        'nama_barang',
        'tgl_beli',
        'unit',
        'harsat',
        'umur_ekonomis',
        'jenis',
        'kategori',
        'status',
        'tgl_validasi',
    ];

    protected $casts = [
        'tgl_beli' => 'date',
        'tgl_validasi' => 'date',
        'umur_ekonomis' => 'integer',
        'unit' => 'integer',
        'harsat' => 'decimal:2',
    ];
}