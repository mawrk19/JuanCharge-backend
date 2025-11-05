<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateKioskUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->string('total_recyclables_weight')->nullable();
            $table->string('total_charging_time')->nullable();
            // deleted_at already exists, so we don't add it again
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->dropColumn(['total_recyclables_weight', 'total_charging_time']);
        });
    }
}
