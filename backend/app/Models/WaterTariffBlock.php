<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WaterTariffBlock extends Model
{
    protected $fillable = [
        'package_id',
        'usage_min_m3',
        'usage_max_m3',
        'price_per_m3'
    ];

    public function package()
    {
        return $this->belongsTo(InstallationPackage::class, 'package_id');
    }
}
