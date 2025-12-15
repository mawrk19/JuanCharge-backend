<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordResetToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'email',
        'token',
        'user_type',
        'expires_at'
    ];

    protected $casts = [
        'expires_at' => 'datetime'
    ];
}
