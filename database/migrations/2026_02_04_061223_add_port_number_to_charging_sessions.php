<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPortNumberToChargingSessions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('charging_sessions', function (Blueprint $table) {
            $table->integer('port_number')->nullable()->after('kiosk_id');
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
            $table->dropColumn('port_number');
        });
    }
}
