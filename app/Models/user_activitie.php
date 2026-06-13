<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class user_activitie extends Model
{
    protected $fillable = [
        'user_id',
        'module',
        'activity',
        'description',
        'old_data',
        'new_data',
    ];

    protected $casts = [
        'old_data' => 'array',
        'new_data' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
