<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ticket extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'ticket_number',
        'type',
        'user_id',
        'status',
        'priority',
        'estimated_finish',
        'completed_at',
        'is_public',
        'booking_date',
        'session',
        'queue_number',
        'scheduled_start',
        'scheduled_end',
    ];

    protected $casts = [
        'is_public' => 'boolean',
        'estimated_finish' => 'datetime',
        'completed_at' => 'datetime',
        'booking_date' => 'date',
        'scheduled_start' => 'datetime',
        'scheduled_end' => 'datetime',
    ];

    public function perbaikans()
    {
        return $this->hasMany(perbaikan::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function penginstalan()
    {
        return $this->hasOne(Penginstalan::class);
    }
    public function perbaikan()
    {
        return $this->hasOne(Perbaikan::class);
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
