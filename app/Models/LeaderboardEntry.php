<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardEntry extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'user_id',
        'total_recycled_weight',
        'rank',
        'bonus_points_awarded',
    ];

    protected $casts = [
        'total_recycled_weight' => 'float',
        'rank' => 'integer',
        'bonus_points_awarded' => 'integer',
    ];

    public function season()
    {
        return $this->belongsTo(LeaderboardSeason::class, 'season_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
