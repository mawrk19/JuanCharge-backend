<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateUsersAddSoftDelete extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lgu_users', function (Blueprint $table) {
            $table->string('status')->default('active');
            $table->timestamp('deleted_at')->nullable();
        });


         Schema::table('kiosk_users', function (Blueprint $table) {
            $table->string('status')->default('active');
            $table->timestamp('deleted_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        //
    }
}
