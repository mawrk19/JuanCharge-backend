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
        'serial_number',
        'mac_address',
        'ip_address',
        'software_version',
        'assigned_to',
        'notes',
        'last_active',
        'registered_at',
    ];

    protected $casts = [
        'last_active' => 'datetime',
        'registered_at' => 'datetime',
    ];

    /**
     * Get the LGU user assigned to this kiosk
     * Using 'assigned_to' as the foreign key column
     */
    public function assignedTo()
    {
        return $this->belongsTo(LguUser::class, 'assigned_to', 'id');
    }

    protected $appends = ['assigned_user_name'];

public function getAssignedUserNameAttribute()
{
    return $this->assignedTo ? $this->assignedTo->name : null;
}

}