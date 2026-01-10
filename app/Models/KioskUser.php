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
        'device_token',
        'token_expires_at',
        'email_verified_at',
        'contact_number_verified_at',
    ];

    protected $hidden = [
        'password',
        'device_token',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
        'email_verified_at' => 'datetime',
        'contact_number_verified_at' => 'datetime',
    ];

    /**
     * Get the charging sessions for the user
     */
    public function chargingSessions()
    {
        return $this->hasMany(ChargingSession::class, 'user_id');
    }

    /**
     * Get the recycling logs for the user
     */
    public function recyclingLogs()
    {
        return $this->hasMany(RecyclingLog::class, 'user_id');
    }

    /**
     * Get the points transactions for the user
     */
    public function pointsTransactions()
    {
        return $this->hasMany(PointsTransaction::class, 'user_id');
    }
}