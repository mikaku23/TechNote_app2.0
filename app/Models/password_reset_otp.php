<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class password_reset_otp extends Model
{
    protected $fillable = [
        'user_id',
        'otp',
        'expired_at',
        'used_at',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
