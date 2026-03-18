<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_notifications', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('lgu_id');
            $table->unsignedBigInteger('collection_schedule_id')->nullable();
            $table->unsignedBigInteger('kiosk_id')->nullable();
            $table->string('title');
            $table->text('message');
            $table->timestamp('scheduled_for')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('cascade');
            $table->foreign('collection_schedule_id')->references('id')->on('collection_schedules')->onDelete('set null');
            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('set null');

            $table->index(['user_id', 'read_at']);
            $table->index(['lgu_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_notifications');
    }
};
