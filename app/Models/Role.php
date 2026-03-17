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

    public function users()
    {
        return $this->hasMany(User::class);
    }
}
