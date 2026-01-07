<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class EnhancePointsTransactionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('points_transactions', function (Blueprint $table) {
            $table->enum('type', ['charge', 'recycling', 'achievement', 'other'])->default('other')->after('transaction_type');
            $table->string('title')->nullable()->after('type');
            $table->string('status')->default('completed')->after('points');
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
            $table->dropColumn(['type', 'title', 'status']);
        });
    }
}
