<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AkunLevel1 extends Model
{
    protected $table = 'akun_level_1';

    protected $fillable = [
        'lev1',
        'lev2',
        'lev3',
        'lev4',
        'kode_akun',
        'nama_akun',
        'jenis_mutasi',
    ];

    protected $casts = [
        'lev1' => 'integer',
        'lev2' => 'integer',
        'lev3' => 'integer',
        'lev4' => 'integer',
    ];

    public function akunLevel2(): HasMany
    {
        return $this->hasMany(AkunLevel2::class, 'parent_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'lev1', 'lev1');
    }
}
