<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MasterArusKas extends Model
{
    protected $table = 'master_arus_kas';
    protected $fillable = ['nama_akun', 'debit', 'kredit', 'parent_id'];

    public function children(): HasMany
    {
        return $this->hasMany(MasterArusKas::class, 'parent_id');
    }

    public function rek_debit(): BelongsTo
    {
        return $this->belongsTo(AkunLevel3::class, 'debit', 'kode_akun');
    }

    public function rek_kredit(): BelongsTo
    {
        return $this->belongsTo(AkunLevel3::class, 'kredit', 'kode_akun');
    }
}
