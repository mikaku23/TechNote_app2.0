<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ai_log extends Model
{
    protected $fillable = [
        'user_id',
        'role',
        'question',
        'answer',
        'action',
        'source',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function actionLogs()
    {
        return $this->hasMany(ai_action_log::class);
    }
}
