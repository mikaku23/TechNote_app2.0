<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class notification extends Model
{
    protected $fillable = [
        'user_id',
        'ticket_id',
        'type',
        'title',
        'message',
        'is_read',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }
}
