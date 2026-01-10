<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddMissingColumnsToPointsTransactions extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('points_transactions', function (Blueprint $table) {
            if (!Schema::hasColumn('points_transactions', 'type')) {
                $table->enum('type', ['charge', 'recycling', 'achievement', 'other'])->default('other')->after('transaction_type');
            }
            if (!Schema::hasColumn('points_transactions', 'title')) {
                $table->string('title')->nullable()->after('type');
            }
            if (!Schema::hasColumn('points_transactions', 'status')) {
                $table->string('status')->default('completed')->after('points');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('points_transactions', function (Blueprint $table) {
            if (Schema::hasColumn('points_transactions', 'type')) {
                $table->dropColumn('type');
            }
            if (Schema::hasColumn('points_transactions', 'title')) {
                $table->dropColumn('title');
            }
            if (Schema::hasColumn('points_transactions', 'status')) {
                $table->dropColumn('status');
            }
        });
    }
}
