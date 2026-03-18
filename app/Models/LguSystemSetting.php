<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LguSystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'lgu_id',
        'minutes_per_bottle',
        'minutes_per_kg',
        'points_per_kg',
    ];

    protected $casts = [
        'minutes_per_bottle' => 'float',
        'minutes_per_kg' => 'float',
        'points_per_kg' => 'integer',
    ];

    public function lgu()
    {
        return $this->belongsTo(Lgu::class);
    }
}
