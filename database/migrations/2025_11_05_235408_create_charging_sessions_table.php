<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChargingSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('charging_sessions', function (Blueprint $table) {
            $table->id();
            $table->string('session_id', 50)->unique();
            $table->unsignedBigInteger('user_id');
            $table->integer('points_redeemed');
            $table->decimal('energy_wh', 10, 2);
            $table->integer('duration_minutes');
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->enum('status', ['active', 'completed', 'cancelled'])->default('active');
            $table->dateTime('cancelled_at')->nullable();
            $table->dateTime('completed_at')->nullable();
            $table->timestamps();

            // Foreign key
            $table->foreign('user_id')->references('id')->on('kiosk_users')->onDelete('cascade');
            
            // Indexes
            $table->index(['user_id', 'status']);
            $table->index('session_id');
            $table->index(['status', 'end_time']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('charging_sessions');
    }
}
