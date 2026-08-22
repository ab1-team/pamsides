<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class AkunLevel3 extends Model
{
    protected $table = 'akun_level_3';

    protected $fillable = [
        'parent_id',
        'lev1',
        'lev2',
        'lev3',
        'lev4',
        'kode_akun',
        'nama_akun',
        'posisi',
        'jenis_mutasi',
    ];

    protected $casts = [
        'parent_id' => 'integer',
        'lev1' => 'integer',
        'lev2' => 'integer',
        'lev3' => 'integer',
        'lev4' => 'integer',
        'posisi' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(AkunLevel2::class, 'parent_id');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function accountParent(): HasMany
    {
        return $this->hasMany(Account::class, 'parent_id');
    }

    public function amount(): HasManyThrough
    {
        return $this->hasManyThrough(Amount::class, Account::class, 'lev3', 'account_id', 'lev3', 'id');
    }
}
