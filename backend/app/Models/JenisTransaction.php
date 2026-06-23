<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JenisTransaction extends Model
{
    protected $table = 'jenis_transactions';

    protected $fillable = [
        'nama_jt',
    ];
}
