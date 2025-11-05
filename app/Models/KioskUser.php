<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;

class KioskUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;
    
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
        'total_recyclables_weight',
        'total_charging_time',
    ];

    protected $hidden = [
        'password',
    ];
}