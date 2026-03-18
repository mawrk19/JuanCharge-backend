<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leaderboard_archives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('leaderboard_seasons')->onDelete('cascade');
            $table->json('snapshot_json');
            $table->timestamps();

            $table->unique('season_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_archives');
    }
};
