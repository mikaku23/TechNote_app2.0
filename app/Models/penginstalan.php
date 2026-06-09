<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class penginstalan extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'software_id',
        'installation_result',
        'note',
    ];

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(user::class);
    }

    public function software()
    {
        return $this->belongsTo(software::class);
    }
}
