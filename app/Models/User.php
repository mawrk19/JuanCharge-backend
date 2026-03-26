<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class User extends Authenticatable implements JWTSubject, MustVerifyEmail
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
        'email_verified_at',
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

    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user')->withTimestamps();
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

    public function collectionNotifications()
    {
        return $this->hasMany(CollectionNotification::class);
    }

    /**
     * Helpers for roles
     */
    public function roleSlug(): ?string
    {
        $slugs = $this->roleSlugs();
        return !empty($slugs) ? (string) $slugs[0] : null;
    }

    public function roleSlugs(): array
    {
        $slugs = [];

        // Keep primary role_id as first for backward compatibility.
        $primary = null;
        if ($this->relationLoaded('role') && $this->role) {
            $primary = $this->role->slug;
        } else {
            $primary = Role::slugForId((int) $this->role_id);
            if ($primary === null && $this->role_id) {
                $primary = Role::query()->where('id', (int) $this->role_id)->value('slug');
            }
        }

        if (!empty($primary)) {
            $slugs[] = (string) $primary;
        }

        try {
            if (Schema::hasTable('role_user')) {
                if ($this->relationLoaded('roles')) {
                    $extra = $this->roles->pluck('slug')->filter()->values()->all();
                } else {
                    $extra = $this->roles()->pluck('roles.slug')->filter()->values()->all();
                }

                foreach ($extra as $slug) {
                    if (!in_array($slug, $slugs, true)) {
                        $slugs[] = (string) $slug;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('roleSlugs fallback to primary role only', ['user_id' => $this->id, 'error' => $e->getMessage()]);
        }

        return $slugs;
    }

    public function roleIds(): array
    {
        $ids = [];

        if (!empty($this->role_id)) {
            $ids[] = (int) $this->role_id;
        }

        try {
            if (Schema::hasTable('role_user')) {
                if ($this->relationLoaded('roles')) {
                    $extra = $this->roles->pluck('id')->all();
                } else {
                    $extra = $this->roles()->pluck('roles.id')->all();
                }

                foreach ($extra as $id) {
                    $id = (int) $id;
                    if (!in_array($id, $ids, true)) {
                        $ids[] = $id;
                    }
                }
            }
        } catch (\Throwable $e) {
            Log::warning('roleIds fallback to primary role only', ['user_id' => $this->id, 'error' => $e->getMessage()]);
        }

        return $ids;
    }

    public function hasRole(int|string $role): bool
    {
        if (is_int($role)) {
            return in_array($role, $this->roleIds(), true);
        }

        return in_array($role, $this->roleSlugs(), true);
    }

    public function hasAnyRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    public function isSuperAdmin()
    {
        return $this->hasRole(Role::SUPER_ADMIN);
    }

    public function isLguAdmin()
    {
        return $this->hasRole(Role::LGU_ADMIN);
    }

    public function isLguStaff()
    {
        return $this->hasRole(Role::LGU_STAFF);
    }

    public function isLguTechnician()
    {
        return $this->hasRole(Role::LGU_TECHNICIAN);
    }

    public function isKioskUser()
    {
        return $this->hasRole(Role::KIOSK_USER);
    }

    public function isLguRole()
    {
        return $this->hasAnyRole([Role::LGU_ADMIN, Role::LGU_STAFF, Role::LGU_TECHNICIAN]);
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
            'role' => $this->roleSlug(),
            'roles' => $this->roleSlugs(),
            'lgu_id' => $this->lgu_id,
        ];
    }
}
