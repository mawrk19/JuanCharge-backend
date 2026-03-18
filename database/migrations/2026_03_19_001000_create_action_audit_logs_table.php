<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('action_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('actor_user_id')->nullable();
            $table->unsignedBigInteger('actor_role_id')->nullable();
            $table->unsignedBigInteger('actor_lgu_id')->nullable();
            $table->string('http_method', 10);
            $table->string('route_path');
            $table->string('route_name')->nullable();
            $table->string('controller_action')->nullable();
            $table->unsignedSmallInteger('status_code')->nullable();
            $table->string('ip_address', 64)->nullable();
            $table->text('user_agent')->nullable();
            $table->json('query_params')->nullable();
            $table->json('payload')->nullable();
            $table->json('response_meta')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('actor_user_id')->references('id')->on('users')->onDelete('set null');
            $table->index(['actor_lgu_id', 'created_at']);
            $table->index(['actor_user_id', 'created_at']);
            $table->index(['http_method', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('action_audit_logs');
    }
};
