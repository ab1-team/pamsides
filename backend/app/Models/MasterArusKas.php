<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MasterArusKas extends Model
{
    protected $table = 'master_arus_kas';

    protected $fillable = [
        'nama_akun',
        'debit',
        'kredit',
        'parent_id',
    ];

    protected $casts = [
        'parent_id' => 'integer',
    ];

    public function children(): HasMany
    {
        return $this->hasMany(MasterArusKas::class, 'parent_id');
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(MasterArusKas::class, 'parent_id');
    }
}
