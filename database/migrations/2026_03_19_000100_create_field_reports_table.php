<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('field_reports', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kiosk_id');
            $table->unsignedBigInteger('submitted_by_user_id');
            $table->enum('activity_type', ['collection_completed', 'cleaning', 'repair']);
            $table->enum('condition_assessment', ['good', 'damaged', 'needs_attention']);
            $table->text('notes')->nullable();
            $table->string('schedule_name')->nullable();
            $table->json('schedule_days')->nullable();
            $table->enum('schedule_alignment', ['aligned', 'out_of_schedule']);
            $table->timestamp('submitted_at');
            $table->enum('verification_status', ['pending', 'verified', 'rejected'])->default('pending');
            $table->timestamp('verified_at')->nullable();
            $table->unsignedBigInteger('verified_by_user_id')->nullable();
            $table->boolean('forced_status_update')->default(false);
            $table->timestamp('forced_status_at')->nullable();
            $table->unsignedBigInteger('forced_by_user_id')->nullable();
            $table->timestamps();

            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('cascade');
            $table->foreign('submitted_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('verified_by_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('forced_by_user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['kiosk_id', 'submitted_at']);
            $table->index(['verification_status', 'activity_type']);
            $table->index(['submitted_by_user_id', 'submitted_at']);
            $table->index('schedule_alignment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_reports');
    }
};
