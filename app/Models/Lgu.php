<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lgu extends Model
{
    use SoftDeletes;

    protected $table = 'lgus';

    protected $fillable = [
        'name',
        'region',
        'province',
        'city_municipality',
        'barangay',
        'address',
        'contact_person',
        'contact_email',
        'contact_number',
        'status',
    ];

    protected $casts = [
        'id' => 'integer',
    ];

    public function users()
    {
        return $this->hasMany(User::class, 'lgu_id');
    }

    public function kiosks()
    {
        return $this->hasMany(Kiosk::class, 'lgu_id');
    }
}
