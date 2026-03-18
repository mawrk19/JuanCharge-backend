<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'lgu_id',
        'name',
        'collection_days',
        'notify_time',
        'is_active',
    ];

    protected $casts = [
        'collection_days' => 'array',
        'is_active' => 'boolean',
    ];

    public function lgu()
    {
        return $this->belongsTo(Lgu::class);
    }

    public function kiosks()
    {
        return $this->hasMany(Kiosk::class, 'collection_schedule_id');
    }
}
