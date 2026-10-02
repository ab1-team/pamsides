<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationTicketHistory extends Model
{
    protected $table = 'installation_ticket_histories';

    protected $fillable = [
        'installation_ticket_id',
        'customer_id',
        'package_id',
        'new_package_id',
        'package_name',
        'installation_fee',
        'monthly_abodemen',
        'late_penalty',
        'effective_from',
        'effective_until',
        'total_paid_on_old_package',
        'total_billed_on_old_package',
        'remaining_on_old_package',
        'change_type',
        'reason',
        'changed_by',
    ];

    protected $casts = [
        'installation_fee' => 'decimal:2',
        'monthly_abodemen' => 'decimal:2',
        'late_penalty' => 'decimal:2',
        'total_paid_on_old_package' => 'decimal:2',
        'total_billed_on_old_package' => 'decimal:2',
        'remaining_on_old_package' => 'decimal:2',
        'effective_from' => 'datetime',
        'effective_until' => 'datetime',
    ];

    /**
     * Label bahasa Indonesia untuk change_type.
     */
    public const CHANGE_TYPE_LABELS = [
        'initial'   => 'Paket Awal',
        'upgrade'   => 'Naik Paket',
        'downgrade' => 'Turun Paket',
        'reset'     => 'Reset Paket',
    ];

    public const CHANGE_TYPE_COLORS = [
        'initial'   => 'slate',
        'upgrade'   => 'emerald',
        'downgrade' => 'amber',
        'reset'     => 'rose',
    ];

    /* ---------- Relations ---------- */

    public function ticket()
    {
        return $this->belongsTo(InstallationTicket::class, 'installation_ticket_id');
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /** Paket lama yang di-snapshot pada saat perubahan. */
    public function oldPackage()
    {
        return $this->belongsTo(InstallationPackage::class, 'package_id');
    }

    /** Paket baru tujuan perubahan (null untuk change_type='initial'). */
    public function newPackage()
    {
        return $this->belongsTo(InstallationPackage::class, 'new_package_id');
    }

    public function changedByUser()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    /* ---------- Accessors ---------- */

    public function getChangeTypeLabelAttribute(): string
    {
        return self::CHANGE_TYPE_LABELS[$this->change_type] ?? ucfirst($this->change_type);
    }

    public function getChangeTypeColorAttribute(): string
    {
        return self::CHANGE_TYPE_COLORS[$this->change_type] ?? 'slate';
    }

    /**
     * Apakah paket lama ini masih memiliki tunggakan saat dihendutasi?
     * Berguna untuk badge "Belum Lunas" di popup detail.
     */
    public function getHasOutstandingAttribute(): bool
    {
        return (float) $this->remaining_on_old_package > 0;
    }
}