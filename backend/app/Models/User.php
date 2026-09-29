<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role', 'avatar_path', 'jabatan_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    public function tickets()
    {
        return $this->hasMany(InstallationTicket::class, 'created_by');
    }

    public function surveys()
    {
        return $this->hasMany(SurveyResult::class, 'surveyor_id');
    }

    public function meterReadings()
    {
        return $this->hasMany(MeterReading::class, 'recorded_by');
    }

    public function customers()
    {
        return $this->hasMany(Customer::class, 'user_id');
    }

    public function jabatan(): BelongsTo
    {
        return $this->belongsTo(Jabatan::class, 'jabatan_id');
    }

    /**
     * Cari user pertama yang memiliki jabatan tertentu (case-insensitive).
     * Dipakai oleh pelaporan: contoh User::findByJabatan('Direktur').
     */
    public static function findByJabatan(string $namaJabatan): ?self
    {
        return static::query()
            ->whereHas('jabatan', function ($q) use ($namaJabatan) {
                $q->whereRaw('LOWER(nama_jabatan) = ?', [strtolower($namaJabatan)]);
            })
            ->first();
    }

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
