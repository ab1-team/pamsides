<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'role'])]
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

    public function customer()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }
}
