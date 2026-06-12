<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ticket_status_log extends Model
{
    protected $fillable = [
        'ticket_id',
        'old_status',
        'new_status',
        'note',
        'changed_by',
    ];

    public function ticket()
    {
        return $this->belongsTo(ticket::class);
    }

    public function changedBy()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }

    public function changer()
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
