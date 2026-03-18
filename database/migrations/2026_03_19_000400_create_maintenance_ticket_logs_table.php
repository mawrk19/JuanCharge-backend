<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('maintenance_ticket_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('maintenance_ticket_id');
            $table->enum('action_type', ['created', 'assigned', 'priority_changed', 'status_changed', 'closed']);
            $table->unsignedBigInteger('actor_user_id');
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('maintenance_ticket_id')->references('id')->on('maintenance_tickets')->onDelete('cascade');
            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('cascade');

            $table->index(['maintenance_ticket_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('maintenance_ticket_logs');
    }
};
