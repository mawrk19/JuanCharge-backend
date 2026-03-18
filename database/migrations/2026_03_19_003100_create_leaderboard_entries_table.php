<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('leaderboard_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('season_id')->constrained('leaderboard_seasons')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->decimal('total_recycled_weight', 12, 3)->default(0);
            $table->unsignedInteger('rank')->nullable();
            $table->integer('bonus_points_awarded')->default(0);
            $table->timestamps();

            $table->unique(['season_id', 'user_id']);
            $table->index(['season_id', 'rank']);
            $table->index(['season_id', 'total_recycled_weight']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leaderboard_entries');
    }
};
