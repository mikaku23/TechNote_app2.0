<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class system_setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'description',
    ];

    protected $casts = [
        'value' => 'json',
    ];
}
