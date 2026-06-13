<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class login_log extends Model
{
    protected $table = 'login_logs';

    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'status',
        'latitude',
        'longitude',
        'accuracy_m',
        'distance_from_anchor_m',
        'location_status',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
        'latitude' => 'decimal:8',
        'longitude' => 'decimal:8',
        'accuracy_m' => 'decimal:2',
        'distance_from_anchor_m' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
