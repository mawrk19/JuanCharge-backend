<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'role_id',
        'lgu_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'phone_number',
        'password',
        'points_balance',
        'points_total',
        'points_used',
        'is_first_login',
        'status',
        'device_token',
        'token_expires_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'device_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'contact_number_verified_at' => 'datetime',
        'token_expires_at' => 'datetime',
        'is_first_login' => 'boolean',
    ];

    /**
     * Relationships
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function lgu()
    {
        return $this->belongsTo(Lgu::class);
    }

    public function sessions()
    {
        return $this->hasMany(ChargingSession::class);
    }

    public function recyclingLogs()
    {
        return $this->hasMany(RecyclingLog::class);
    }

    /**
     * Helpers for roles
     */
    public function isSuperAdmin()
    {
        return $this->role_id === Role::SUPER_ADMIN;
    }

    public function isLguAdmin()
    {
        return $this->role_id === Role::LGU_ADMIN;
    }

    public function isLguStaff()
    {
        return $this->role_id === Role::LGU_STAFF;
    }

    public function isKioskUser()
    {
        return $this->role_id === Role::KIOSK_USER;
    }

    /**
     * JWT Implementation
     */
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role ? $this->role->slug : null,
            'lgu_id' => $this->lgu_id,
        ];
    }
}
