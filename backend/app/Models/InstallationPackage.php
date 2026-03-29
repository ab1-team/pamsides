<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationPackage extends Model
{
    protected $fillable = [
        'name',
        'installation_fee',
        'monthly_abodemen',
        'late_penalty'
    ];

    public function tariffBlocks()
    {
        return $this->hasMany(WaterTariffBlock::class, 'package_id');
    }

    public function tickets()
    {
        return $this->hasMany(InstallationTicket::class, 'package_id');
    }
}
