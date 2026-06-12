<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class maintenance extends Model
{
    protected $fillable = [
        'is_active',
        'message',
        'started_at',
        'ended_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'started_at' => 'datetime',
        'ended_at' => 'datetime',
    ];
}
