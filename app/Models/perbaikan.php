<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class perbaikan extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'item_name',
        'item_location',
        'damage_description',
        'repair_action',
        'repair_result',
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
}
