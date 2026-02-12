<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class DropFieldsFromLguUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lgu_users', function (Blueprint $table) {
            $table->dropColumn(['role', 'phone_number', 'birth_date']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('lgu_users', function (Blueprint $table) {
            $table->string('role', 32)->after('name')->nullable();
            $table->date('birth_date')->after('role')->nullable();
            $table->string('phone_number', 15)->after('birth_date')->nullable();
        });
    }
}
