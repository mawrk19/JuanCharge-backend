<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKioskRecyclingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kiosk_recycling_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('kiosk_id');
            $table->string('item_type');
            $table->integer('count');
            $table->timestamp('hardware_timestamp')->nullable();
            $table->timestamps();

            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('kiosk_recycling_logs');
    }
}
