<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('missed_collection_alerts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kiosk_id');
            $table->unsignedBigInteger('lgu_id');
            $table->date('scheduled_date');
            $table->timestamp('cutoff_at');
            $table->timestamp('detected_at');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('cascade');
            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('cascade');
            $table->unique(['kiosk_id', 'scheduled_date']);
            $table->index(['lgu_id', 'resolved_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missed_collection_alerts');
    }
};
