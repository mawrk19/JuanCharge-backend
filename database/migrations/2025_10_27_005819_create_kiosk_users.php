<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateKioskUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('kiosk_users', function (Blueprint $table) {
            $table->id();
            $table->string('name', 64)->unique();
            $table->string('role', 32);
            $table->date('birth_date');
            $table->string('phone_number', 15);
            $table->string('email', 128)->unique();
            $table->string('password');
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
        Schema::dropIfExists('kiosk_users');
    }
}
