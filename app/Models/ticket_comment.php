<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ticket_comment extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'comment',
        'is_internal',
    ];

    protected $casts = [
        'is_internal' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(user::class);
    }
}
