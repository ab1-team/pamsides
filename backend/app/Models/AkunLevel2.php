<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AkunLevel2 extends Model
{
    protected $table = 'akun_level_2';

    protected $fillable = [
        'parent_id',
        'lev1',
        'lev2',
        'lev3',
        'lev4',
        'kode_akun',
        'nama_akun',
        'jenis_mutasi',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'lev1' => 'integer',
        'lev2' => 'integer',
        'lev3' => 'integer',
        'lev4' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AkunLevel1::class, 'parent_id');
    }

    public function akunLevel3(): HasMany
    {
        return $this->hasMany(AkunLevel3::class, 'parent_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'lev2', 'lev2');
    }
}
