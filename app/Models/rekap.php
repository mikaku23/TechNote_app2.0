<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class rekap extends Model
{
    protected $fillable = [
        'rekap_date',
        'total_installations',
        'total_repairs',
        'completed_tickets',
        'failed_tickets',
        'pending_tickets',
    ];
}
