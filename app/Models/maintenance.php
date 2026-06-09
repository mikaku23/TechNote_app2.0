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
}
