<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationTicket extends Model
{
    protected $fillable = [
        'package_id',
        'applicant_name',
        'nik',
        'address',
        'lat',
        'lng',
        'status',
        'created_by'
    ];

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
}
