<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ticket extends Model
{
    protected $fillable = [
        'ticket_number',
        'type',
        'user_id',
        'status',
        'priority',
        'estimated_finish',
        'completed_at',
        'is_public',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penginstalans()
    {
        return $this->hasMany(penginstalan::class);
    }

    public function perbaikans()
    {
        return $this->hasMany(perbaikan::class);
    }

    public function statusLogs()
    {
        return $this->hasMany(ticket_status_log::class);
    }

    public function comments()
    {
        return $this->hasMany(ticket_comment::class);
    }

    public function notifications()
    {
        return $this->hasMany(notification::class);
    }

    public function aiRecommendations()
    {
        return $this->hasMany(ai_recommendation::class);
    }

    public function vercelSyncLogs()
    {
        return $this->hasMany(vercel_sync_log::class);
    }
}
