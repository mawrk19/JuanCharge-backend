<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLguIdToLguUsersAndKiosks extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('lgu_users', function (Blueprint $table) {
            $table->unsignedBigInteger('lgu_id')->nullable()->after('id');
            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('set null');
        });

        Schema::table('kiosks', function (Blueprint $table) {
            $table->unsignedBigInteger('lgu_id')->nullable()->after('id');
            $table->foreign('lgu_id')->references('id')->on('lgus')->onDelete('set null');
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
            $table->dropForeign(['lgu_id']);
            $table->dropColumn('lgu_id');
        });

        Schema::table('kiosks', function (Blueprint $table) {
            $table->dropForeign(['lgu_id']);
            $table->dropColumn('lgu_id');
        });
    }
}
