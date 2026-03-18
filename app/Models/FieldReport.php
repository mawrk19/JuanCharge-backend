<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FieldReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'kiosk_id',
        'submitted_by_user_id',
        'activity_type',
        'condition_assessment',
        'notes',
        'schedule_name',
        'schedule_days',
        'schedule_alignment',
        'submitted_at',
        'verification_status',
        'verified_at',
        'verified_by_user_id',
        'forced_status_update',
        'forced_status_at',
        'forced_by_user_id',
    ];

    protected $casts = [
        'schedule_days' => 'array',
        'submitted_at' => 'datetime',
        'verified_at' => 'datetime',
        'forced_status_update' => 'boolean',
        'forced_status_at' => 'datetime',
    ];

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by_user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by_user_id');
    }

    public function forcedBy()
    {
        return $this->belongsTo(User::class, 'forced_by_user_id');
    }

    public function photos()
    {
        return $this->hasMany(FieldReportPhoto::class);
    }

    public function ticket()
    {
        return $this->hasOne(MaintenanceTicket::class);
    }
}
