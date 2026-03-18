<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lgu_system_settings', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lgu_id')->unique();
            $table->decimal('minutes_per_bottle', 8, 2)->default(0);
            $table->decimal('minutes_per_kg', 8, 2)->default(0);
            $table->integer('points_per_kg')->default(0);
            $table->timestamps();

            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lgu_system_settings');
    }
};
