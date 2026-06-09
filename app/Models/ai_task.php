<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ai_task extends Model
{
    protected $fillable = [
        'user_id',
        'task_name',
        'instruction',
        'status',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
