<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'ticket_id',
        'amount',
        'type',
        'status',
        'confirmed_by',
        'paid_at'
    ];

    public function ticket()
    {
        return $this->belongsTo(InstallationTicket::class, 'ticket_id');
    }
}
