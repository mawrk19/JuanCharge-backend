<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Monolog\Handler\FirePHPHandler;

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
        'first_name',
        'last_name',
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
                $lguUser->password = static::generateDefaultPassword($lguUser->first_name ?? $lguUser->name, $lguUser->birth_date);
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
     * Generate default password from first name and birth date.
     * Format: firstname + MMDDYYYY (e.g., "john05151990")
     *
     * @param string $firstName First name
     * @param string|\Carbon\Carbon $birthDate Birth date
     * @return string Hashed password
     */
    public static function generateDefaultPassword($firstName, $birthDate)
    {
        // Clean and lowercase first name
        $cleanFirstName = strtolower(trim($firstName));
        
        // Format birth date as MMDDYYYY
        $date = $birthDate instanceof \Carbon\Carbon ? $birthDate : \Carbon\Carbon::parse($birthDate);
        $dateString = $date->format('mdY'); // e.g., "05151990" for May 15, 1990

        // Combine firstname + MMDDYYYY
        $plainPassword = $cleanFirstName . $dateString;

        // Return hashed password
        return Hash::make($plainPassword);
    }

    /**
     * Get the plain default password (for display/testing purposes only).
     *
     * @param string $firstName First name
     * @param string|\Carbon\Carbon $birthDate Birth date
     * @return string Plain password (not hashed)
     */
    public static function getPlainDefaultPassword($firstName, $birthDate)
    {
        // Clean and lowercase first name
        $cleanFirstName = strtolower(trim($firstName));
        
        // Format birth date as MMDDYYYY
        $date = $birthDate instanceof \Carbon\Carbon ? $birthDate : \Carbon\Carbon::parse($birthDate);
        $dateString = $date->format('mdY');

        return $cleanFirstName . $dateString; // e.g., "john05151990"
    }

    
}
