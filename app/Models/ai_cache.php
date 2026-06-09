<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ai_cache extends Model
{
    protected $table = 'ai_cache';

    protected $fillable = [
        'cache_key',
        'question',
        'answer',
        'source',
        'expired_at',
    ];
}
