<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('point_vouchers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique(); // The QR code content (e.g., UUID)
            $table->integer('points');
            $table->foreignId('kiosk_id')->nullable()->constrained('kiosks')->nullOnDelete();
            $table->enum('status', ['pending', 'claimed', 'expired', 'revoked'])->default('pending');
            $table->foreignId('claimed_by')->nullable()->constrained('kiosk_users')->nullOnDelete(); // User who claimed it
            $table->timestamp('claimed_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->json('metadata')->nullable(); // Store items/recyclables info
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('point_vouchers');
    }
};
