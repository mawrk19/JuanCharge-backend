<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;

class KioskUser extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'kiosk_users';

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

        static::creating(function ($kioskUser) {
            // Only generate password if not provided
            if (empty($kioskUser->password)) {
                $kioskUser->password = static::generateDefaultPassword($kioskUser->name, $kioskUser->birth_date);
            } else {
                // If password is provided, hash it
                $kioskUser->password = Hash::make($kioskUser->password);
            }
        });

        static::updating(function ($kioskUser) {
            // Only hash if password is being changed and not already hashed
            if ($kioskUser->isDirty('password') && !empty($kioskUser->password)) {
                // Check if it's not already a bcrypt hash (bcrypt hashes start with $2y$)
                if (!str_starts_with($kioskUser->password, '$2y$')) {
                    $kioskUser->password = Hash::make($kioskUser->password);
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
