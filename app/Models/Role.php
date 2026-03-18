<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Role extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'description'];

    // Constants for easy reference in code
    const SUPER_ADMIN = 1;
    const LGU_ADMIN = 2;
    const LGU_STAFF = 3;
    const KIOSK_USER = 4;

    // Slug constants for route and middleware checks
    const SUPER_ADMIN_SLUG = 'super_admin';
    const LGU_ADMIN_SLUG = 'lgu_admin';
    const LGU_STAFF_SLUG = 'lgu_staff';
    const KIOSK_USER_SLUG = 'kiosk_user';

    public static function slugForId(int $roleId): ?string
    {
        return match ($roleId) {
            self::SUPER_ADMIN => self::SUPER_ADMIN_SLUG,
            self::LGU_ADMIN => self::LGU_ADMIN_SLUG,
            self::LGU_STAFF => self::LGU_STAFF_SLUG,
            self::KIOSK_USER => self::KIOSK_USER_SLUG,
            default => null,
        };
    }

    public static function idForSlug(string $slug): ?int
    {
        return match ($slug) {
            self::SUPER_ADMIN_SLUG => self::SUPER_ADMIN,
            self::LGU_ADMIN_SLUG => self::LGU_ADMIN,
            self::LGU_STAFF_SLUG => self::LGU_STAFF,
            self::KIOSK_USER_SLUG => self::KIOSK_USER,
            default => null,
        };
    }

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
