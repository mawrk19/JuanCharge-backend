<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardSeason extends Model
{
    use HasFactory;

    protected $fillable = [
        'start_at',
        'end_at',
        'status',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
    ];

    public function entries()
    {
        return $this->hasMany(LeaderboardEntry::class, 'season_id');
    }

    public function archive()
    {
        return $this->hasOne(LeaderboardArchive::class, 'season_id');
    }
}
