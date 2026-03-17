<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RecyclingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'kiosk_id',
        'kiosk_code',
        'transaction_id',
        'weight_kg',
        'points_earned',
        'item_type',
        'count',
        'hardware_timestamp',
        'status',
        'notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class, 'kiosk_id');
    }
}
