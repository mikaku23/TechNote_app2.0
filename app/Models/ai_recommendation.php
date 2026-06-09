<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ai_recommendation extends Model
{
    protected $fillable = [
        'ticket_id',
        'recommendation',
        'reason',
        'status',
    ];

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }
}
