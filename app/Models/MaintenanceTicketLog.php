<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaintenanceTicketLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'maintenance_ticket_id',
        'action_type',
        'actor_user_id',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function ticket()
    {
        return $this->belongsTo(MaintenanceTicket::class, 'maintenance_ticket_id');
    }

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
