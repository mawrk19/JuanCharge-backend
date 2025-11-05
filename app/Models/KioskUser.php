<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class KioskUser extends Model
{
    
    protected $table = 'kiosk_users';

    protected $fillable = [
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'contact_number',
        'points_balance',
        'points_total',
        'points_used',
        'leaderboard_rank',
    ];

    protected $hidden = [
        'password',
    ];
}