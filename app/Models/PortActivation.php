<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PortActivation extends Model
{
    use HasFactory;

    protected $fillable = [
        'kiosk_id',
        'user_id',
        'port_number',
        'status',
        'points',
        'duration_seconds',
    ];

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
