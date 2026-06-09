<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class software extends Model
{
    protected $fillable = [
        'name',
        'developer',
        'version',
        'description',
    ];

    public function penginstalans()
    {
        return $this->hasMany(penginstalan::class);
    }
}
