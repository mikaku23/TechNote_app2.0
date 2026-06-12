<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ai_action_log extends Model
{
    protected $fillable = [
        'ai_log_id',
        'action_type',
        'action_data',
        'result',
    ];

    protected $casts = [
        'action_data' => 'json',
    ];

    public function aiLog()
    {
        return $this->belongsTo(ai_log::class, 'ai_log_id');
    }
}
