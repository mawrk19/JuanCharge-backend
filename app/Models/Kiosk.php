<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Kiosk extends Model
{
    use HasFactory;

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
        'registered_at',
        'last_active',
    ];

    protected $casts = [
        'registered_at' => 'datetime',
        'last_active' => 'datetime',
    ];

    public function assignedTo()
    {
        return $this->belongsTo(\App\Models\LguUser::class, 'assigned_to');
    }
}
