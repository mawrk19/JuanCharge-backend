<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class LguUser extends Authenticatable
{
    use HasFactory, HasApiTokens, Notifiable;

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
        'name',
        'role',
        'birth_date',
        'phone_number',
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
        'birth_date' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Boot the model and register the creating event.
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($lguUser) {
            // Only generate password if not provided
            if (empty($lguUser->password)) {
                $lguUser->password = static::generateDefaultPassword($lguUser->name, $lguUser->birth_date);
            } else {
                // If password is provided, hash it
                $lguUser->password = Hash::make($lguUser->password);
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
     * Generate default password from lastname and birthday.
     * Format: lastname + MMDD (e.g., "acedo0319")
     *
     * @param string $name Full name (e.g., "Mark Acedo")
     * @param string|\Carbon\Carbon $birthDate Birth date
     * @return string Hashed password
     */
    public static function generateDefaultPassword($name, $birthDate)
    {
        // Extract last name (last word in the name)
        $nameParts = explode(' ', trim($name));
        $lastName = strtolower(end($nameParts));

        // Format birth date as MMDD
        $date = $birthDate instanceof \Carbon\Carbon ? $birthDate : \Carbon\Carbon::parse($birthDate);
        $monthDay = $date->format('md'); // e.g., "0319" for March 19

        // Combine lastname + MMDD
        $plainPassword = $lastName . $monthDay;

        // Return hashed password
        return Hash::make($plainPassword);
    }

    /**
     * Get the plain default password (for display/testing purposes only).
     *
     * @param string $name Full name
     * @param string|\Carbon\Carbon $birthDate Birth date
     * @return string Plain password (not hashed)
     */
    public static function getPlainDefaultPassword($name, $birthDate)
    {
        $nameParts = explode(' ', trim($name));
        $lastName = strtolower(end($nameParts));

        $date = $birthDate instanceof \Carbon\Carbon ? $birthDate : \Carbon\Carbon::parse($birthDate);
        $monthDay = $date->format('md');

        return $lastName . $monthDay;
    }
}
