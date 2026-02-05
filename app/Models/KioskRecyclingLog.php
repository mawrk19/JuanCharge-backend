<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KioskRecyclingLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'kiosk_id',
        'item_type',
        'count',
        'hardware_timestamp',
    ];

    protected $casts = [
        'hardware_timestamp' => 'datetime',
        'count' => 'integer',
    ];

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }
}
