<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LeaderboardArchive extends Model
{
    use HasFactory;

    protected $fillable = [
        'season_id',
        'snapshot_json',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
    ];

    public function season()
    {
        return $this->belongsTo(LeaderboardSeason::class, 'season_id');
    }
}
