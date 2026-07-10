<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calk extends Model
{
    protected $table = 'calks';

    protected $fillable = [
        'tanggal',
        'catatan',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];
}