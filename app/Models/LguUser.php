<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;


class LguUser extends Authenticatable implements MustVerifyEmail
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'lgu_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'lgu_id',
        'name',
        'first_name',
        'last_name',
        'email',
        'password',
        'is_first_login',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_first_login' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function lgu()
    {
        return $this->belongsTo(Lgu::class, 'lgu_id');
    }

    public function kiosks()
    {
        return $this->hasMany(Kiosk::class, 'assigned_to');
    }

    /**
     * Boot the model and register the creating event.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lguUser) {
            // Auto-generate name if not provided
            if (empty($lguUser->name) && !empty($lguUser->first_name) && !empty($lguUser->last_name)) {
                $lguUser->name = trim($lguUser->first_name . ' ' . $lguUser->last_name);
            }

            // Only generate password if not provided
            if (empty($lguUser->password)) {
                $lguUser->password = static::generateDefaultPassword();
            } else {
                // If password is provided and not already hashed, hash it
                if (!str_starts_with($lguUser->password, '$2y$')) {
                    $lguUser->password = Hash::make($lguUser->password);
                }
            }
        });

        static::updating(function ($lguUser) {
            // Only hash if password is being changed and not already hashed
            if ($lguUser->isDirty('password') && !empty($lguUser->password)) {
                // Check if it's not already a bcrypt hash (bcrypt hashes start with $2y$)
                if (!str_starts_with($lguUser->password, '$2y$')) {
                    $lguUser->password = Hash::make($lguUser->password);
                }
            }
        });
    }

    /**
     * Generate default password.
     * Format: secure random string
     *
     * @return string Hashed password
     */
    public static function generateDefaultPassword()
    {
        $plainPassword = \Illuminate\Support\Str::random(10);
        return Hash::make($plainPassword);
    }

    /**
     * Get a plain random password (for display/testing purposes only).
     *
     * @return string Plain password (not hashed)
     */
    public static function getPlainDefaultPassword()
    {
        return \Illuminate\Support\Str::random(10);
    }


}
