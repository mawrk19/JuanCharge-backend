<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ConsolidateRecyclingLogsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recycling_logs', function (Blueprint $table) {
            // Add quantity and hardware timestamp
            $table->integer('count')->default(1)->after('item_type');
            $table->timestamp('hardware_timestamp')->nullable()->after('count');
        });

        // Use raw SQL to make user_id nullable to avoid doctrine/dbal dependency
        try {
            DB::statement('ALTER TABLE recycling_logs MODIFY user_id BIGINT UNSIGNED NULL');
        } catch (\Exception $e) {
            // Fallback for different DB drivers or already changed
            Log::info('Migration note: Manual ALTER TABLE failed or bypassed: ' . $e->getMessage());
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('recycling_logs', function (Blueprint $table) {
            $table->dropColumn(['count', 'hardware_timestamp']);
        });

        try {
            DB::statement('ALTER TABLE recycling_logs MODIFY user_id BIGINT UNSIGNED NOT NULL');
        } catch (\Exception $e) {
            Log::info('Migration note: Manual ALTER TABLE down failed: ' . $e->getMessage());
        }
    }
}
