<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActionAuditLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'actor_user_id',
        'actor_role_id',
        'actor_lgu_id',
        'http_method',
        'route_path',
        'route_name',
        'controller_action',
        'status_code',
        'ip_address',
        'user_agent',
        'query_params',
        'payload',
        'response_meta',
        'created_at',
    ];

    protected $casts = [
        'query_params' => 'array',
        'payload' => 'array',
        'response_meta' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
