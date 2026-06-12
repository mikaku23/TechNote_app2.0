<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class perbaikan extends Model
{
    use SoftDeletes;

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
        return $this->belongsTo(ticket::class)->withTrashed();
    }

    public function user()
    {
        return $this->belongsTo(user::class);
    }
}
