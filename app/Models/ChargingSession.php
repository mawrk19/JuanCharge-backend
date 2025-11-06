<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ChargingSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'session_id',
        'user_id',
        'kiosk_id',
        'points_redeemed',
        'energy_wh',
        'duration_minutes',
        'start_time',
        'end_time',
        'status',
        'cancelled_at',
        'completed_at',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'cancelled_at' => 'datetime',
        'completed_at' => 'datetime',
        'energy_wh' => 'decimal:2',
    ];

    /**
     * Get the user that owns the charging session
     */
    public function user()
    {
        return $this->belongsTo(KioskUser::class, 'user_id');
    }

    /**
     * Get the kiosk used for this charging session
     */
    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class, 'kiosk_id');
    }

    /**
     * Calculate remaining minutes for active session
     */
    public function getRemainingMinutesAttribute()
    {
        if ($this->status !== 'active') {
            return 0;
        }

        $now = now();
        if ($now->greaterThanOrEqualTo($this->end_time)) {
            return 0;
        }

        return $now->diffInMinutes($this->end_time);
    }

    /**
     * Check if session is expired
     */
    public function isExpired()
    {
        return now()->greaterThanOrEqualTo($this->end_time);
    }
}
