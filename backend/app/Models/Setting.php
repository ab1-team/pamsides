<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = ['key', 'value'];

    public function villages()
    {
        return $this->hasMany(Village::class, 'setting_id');
    }
}
