<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PointVoucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'points',
        'kiosk_id',
        'status',
        'claimed_by',
        'claimed_at',
        'expires_at',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
        'claimed_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'claimed_by');
    }
}
