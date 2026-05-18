<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Village extends Model
{
    protected $fillable = [
        'village_name',
        'hamlet_name',
        'address',
        'phone',

    ];

    public function kecamatan()
    {
        return $this->belongsTo(Setting::class, 'setting_id');
    }

    public function tickets()
    {
        return $this->hasMany(InstallationTicket::class);
    }
}
