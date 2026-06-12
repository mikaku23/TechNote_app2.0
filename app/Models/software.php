<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class software extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name',
        'developer',
        'version',
        'description',
        'estimated_minutes',
    ];

    public function penginstalans()
    {
        return $this->hasMany(penginstalan::class);
    }
}
