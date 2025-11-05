<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateKiosksUsersAddTotalPointsColumn extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add new columns first
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->integer('points_balance')->default(0)->after('contact_number');
            $table->integer('points_total')->default(0)->after('points_balance');
            $table->integer('points_used')->default(0)->after('points_total');
            $table->string('leaderboard_rank')->nullable()->after('points_used');
            $table->string('role')->default('patron')->before('points_balance');
        });

        // Copy data from 'points' to 'points_balance'
        DB::statement('UPDATE kiosk_users SET points_balance = points');

        // Drop the old 'points' column
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->dropColumn('points');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Add back the old 'points' column
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->integer('points')->default(0)->after('contact_number');
        });

        // Copy data back from 'points_balance' to 'points'
        DB::statement('UPDATE kiosk_users SET points = points_balance');

        // Drop the new columns
        Schema::table('kiosk_users', function (Blueprint $table) {
            $table->dropColumn(['points_balance', 'points_total', 'points_used', 'leaderboard_rank']);
        });
    }
}

