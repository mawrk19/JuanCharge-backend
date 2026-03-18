<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MissedCollectionAlert extends Model
{
    use HasFactory;

    protected $fillable = [
        'kiosk_id',
        'lgu_id',
        'scheduled_date',
        'cutoff_at',
        'detected_at',
        'resolved_at',
    ];

    protected $casts = [
        'scheduled_date' => 'date',
        'cutoff_at' => 'datetime',
        'detected_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function lgu()
    {
        return $this->belongsTo(Lgu::class);
    }
}
