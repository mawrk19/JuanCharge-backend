<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePortActivationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('port_activations', function (Illuminate\Database\Schema\Blueprint $table) {
            $table->id();
            $table->foreignId('kiosk_id')->constrained('kiosks')->onDelete('cascade');
            $table->foreignId('user_id')->constrained('kiosk_users')->onDelete('cascade');
            $table->integer('port_number');
            $table->enum('status', ['pending', 'sent', 'completed', 'expired'])->default('pending');
            $table->integer('points');
            $table->integer('duration_seconds');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('port_activations');
    }
}
