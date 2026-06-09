<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class trusted_website extends Model
{
    protected $fillable = [
        'name',
        'url',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
