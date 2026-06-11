<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class TroubleReport extends Model
{
    protected $table = 'trouble_reports';

    protected $fillable = [
        'customer_id',
        'user_id',
        'trouble_type',
        'description',
        'contact_phone',
        'photo_path',
        'status',
        'admin_note',
        'resolved_at',
        'handled_by',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function handler()
    {
        return $this->belongsTo(User::class, 'handled_by');
    }

    public function getPhotoUrlAttribute(): ?string
    {
        if (empty($this->photo_path)) {
            return null;
        }

        return Storage::url($this->photo_path);
    }
}
