<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_tickets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_report_id')->unique();
            $table->unsignedBigInteger('kiosk_id');
            $table->text('issue_summary');
            $table->enum('status', ['open', 'in_progress', 'closed'])->default('open');
            $table->enum('priority', ['low', 'medium', 'high'])->default('medium');
            $table->unsignedBigInteger('assigned_to_user_id')->nullable();
            $table->unsignedBigInteger('created_by_user_id');
            $table->timestamp('closed_at')->nullable();
            $table->unsignedBigInteger('closed_by_user_id')->nullable();
            $table->text('technician_follow_up')->nullable();
            $table->text('resolution_log')->nullable();
            $table->timestamps();

            $table->foreign('field_report_id')->references('id')->on('field_reports')->onDelete('cascade');
            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('cascade');
            $table->foreign('assigned_to_user_id')->references('id')->on('users')->onDelete('set null');
            $table->foreign('created_by_user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('closed_by_user_id')->references('id')->on('users')->onDelete('set null');

            $table->index(['status', 'priority']);
            $table->index(['assigned_to_user_id', 'status']);
            $table->index('kiosk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_tickets');
    }
};
