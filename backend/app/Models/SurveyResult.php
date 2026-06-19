<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SurveyResult extends Model
{
    protected $fillable = [
        'ticket_id',
        'surveyor_id',
        'distance_to_pipe_m',
        'material_notes',
        'photo_url',
        'surveyed_at',
    ];

    protected $casts = [
        'distance_to_pipe_m' => 'integer',
        'surveyed_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(InstallationTicket::class, 'ticket_id');
    }

    public function surveyor()
    {
        return $this->belongsTo(User::class, 'surveyor_id');
    }

    public function getPhotoUrlAttribute($value)
    {
        if (empty($value)) {
            return null;
        }

        if (preg_match('/^https?:\/\//i', $value)) {
            return $value;
        }

        return $value;
    }
}
