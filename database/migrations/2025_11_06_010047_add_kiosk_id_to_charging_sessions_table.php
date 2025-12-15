<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddKioskIdToChargingSessionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->unsignedBigInteger('kiosk_id')->nullable()->after('user_id');
            $table->foreign('kiosk_id')->references('id')->on('kiosks')->onDelete('set null');
            $table->index('kiosk_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->dropForeign(['kiosk_id']);
            $table->dropColumn('kiosk_id');
        });
    }
}
