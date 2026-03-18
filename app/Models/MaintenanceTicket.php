<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTicket extends Model
{
    use HasFactory;

    protected $fillable = [
        'field_report_id',
        'kiosk_id',
        'issue_summary',
        'status',
        'priority',
        'assigned_to_user_id',
        'created_by_user_id',
        'closed_at',
        'closed_by_user_id',
        'technician_follow_up',
        'resolution_log',
    ];

    protected $casts = [
        'closed_at' => 'datetime',
    ];

    public function fieldReport()
    {
        return $this->belongsTo(FieldReport::class);
    }

    public function kiosk()
    {
        return $this->belongsTo(Kiosk::class);
    }

    public function assignedTo()
    {
        return $this->belongsTo(User::class, 'assigned_to_user_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public function closedBy()
    {
        return $this->belongsTo(User::class, 'closed_by_user_id');
    }

    public function logs()
    {
        return $this->hasMany(MaintenanceTicketLog::class);
    }
}
