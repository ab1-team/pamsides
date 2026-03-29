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
        'surveyed_at'
    ];

    public function ticket()
    {
        return $this->belongsTo(InstallationTicket::class, 'ticket_id');
    }
}
