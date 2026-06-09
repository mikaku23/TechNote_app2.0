<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class vercel_sync_log extends Model
{
    protected $fillable = [
        'ticket_id',
        'sync_status',
        'response',
        'synced_at',
    ];

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }
}
