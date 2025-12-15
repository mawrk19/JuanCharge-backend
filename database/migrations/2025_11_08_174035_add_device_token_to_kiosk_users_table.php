<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDeviceTokenToKioskUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->text('device_token')->nullable()->after('password');
            $table->timestamp('token_expires_at')->nullable()->after('device_token');
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
            $table->dropColumn(['device_token', 'token_expires_at']);
        });
    }
}
