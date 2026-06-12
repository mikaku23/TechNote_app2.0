<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class login_log extends Model
{
    protected $fillable = [
        'user_id',
        'ip_address',
        'user_agent',
        'status',
        'login_at',
        'logout_at',
    ];

    protected $casts = [
        'login_at' => 'datetime',
        'logout_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
