<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Jabatan extends Model
{
    protected $fillable = [
        'nama_jabatan',
    ];

    public function users(): HasMany
    {
        return $this->hasMany(User::class, 'jabatan_id');
    }

    public static function findByName(string $nama)
    {
        return static::query()->whereRaw('LOWER(nama_jabatan) = ?', [strtolower($nama)])->first();
    }
}
