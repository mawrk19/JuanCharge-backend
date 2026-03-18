<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('collection_schedules', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('lgu_id');
            $table->string('name');
            $table->json('collection_days');
            $table->time('notify_time')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('cascade');
            $table->index(['lgu_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('collection_schedules');
    }
};
