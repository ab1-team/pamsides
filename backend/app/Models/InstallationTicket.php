<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationTicket extends Model
{
    protected $fillable = [
        'package_id',
        'user_id',
        'applicant_name',
        'nik',
        'rt',
        'rw',
        'order_date',
        'address',
        'phone',
        'gender',
        'birth_place',
        'birth_date',
        'lat',
        'lng',
        'status',
        'village_id',
        'created_by',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(InstallationPackage::class, 'package_id');
    }

    public function survey()
    {
        return $this->hasMany(SurveyResult::class, 'ticket_id');
    }

    public function payments()
    {
        return $this->hasMany(Payment::class, 'ticket_id');
    }

    public function customer()
    {
        return $this->hasMany(Customer::class, 'ticket_id');
    }

    public function village()
    {
        return $this->belongsTo(Village::class, 'village_id');
    }

    /**
     * Snapshot paket lama untuk tiket ini, diurutkan dari yang terbaru.
     * Paket aktif saat ini tetap dibaca dari `$this->package`.
     */
    public function history()
    {
        return $this->hasMany(InstallationTicketHistory::class, 'installation_ticket_id')
            ->orderByDesc('created_at');
    }

    public function getTotalFeeAttribute(): float
    {
        return (float) ($this->package?->installation_fee ?? 0);
    }

    public function getPaidAmountAttribute(): float
    {
        return (float) $this->payments()
            ->where('status', 'confirmed')
            ->sum('amount');
    }

    public function getRemainingAttribute(): float
    {
        return max(0, $this->total_fee - $this->paid_amount);
    }

    public function getIsFullyPaidAttribute(): bool
    {
        return $this->remaining <= 0;
    }
}
