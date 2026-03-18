<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CollectionNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'lgu_id',
        'collection_schedule_id',
        'kiosk_id',
        'title',
        'message',
        'scheduled_for',
        'read_at',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
        'read_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function lgu()
    {
        return $this->belongsTo(Lgu::class);
    }

    public function schedule()
    {
        return $this->belongsTo(CollectionSchedule::class, 'collection_schedule_id');
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }
}
