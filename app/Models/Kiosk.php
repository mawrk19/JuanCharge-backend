<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    use HasFactory;

    protected $table = 'kiosks';

    protected $fillable = [
        'kiosk_code',
        'location',
        'status',
        'assigned_to',
        'last_active',
        'registered_at',
        'ip_address',
        'details',
    ];

    protected $casts = [
        'last_active' => 'datetime',
        'registered_at' => 'datetime',
        'details' => 'array',
    ];

    /**
     * Get the LGU user assigned to this kiosk
     * Using 'assigned_to' as the foreign key column
     */
    public function assignedTo()
    {
        return $this->belongsTo(LguUser::class, 'assigned_to', 'id');
    }

    /**
     * Get the charging sessions for this kiosk
     */
    public function sessions()
    {
        return $this->hasMany(ChargingSession::class, 'kiosk_id');
    }

    protected $appends = ['assigned_user_name'];

    public function getAssignedUserNameAttribute()
    {
        return $this->assignedTo ? $this->assignedTo->name : null;
    }

}