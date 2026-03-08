<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AdjustTransactionIdIndexOnRecyclingLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recycling_logs', function (Blueprint $table) {
            // attempt to drop the old composite key, ignore errors if absent
            try {
                $table->dropIndex('recycling_logs_kiosk_code_transaction_id_index');
            } catch (\Exception $e) {
                // index may not exist yet, safe to ignore
            }

            // create simple index on transaction_id; if already present the call is idempotent
            try {
                $table->index('transaction_id');
            } catch (\Exception $e) {
                // ignore duplicated index error
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
        Schema::table('recycling_logs', function (Blueprint $table) {
            // drop the simple index if present
            try {
                $table->dropIndex('recycling_logs_transaction_id_index');
            } catch (\Exception $e) {
                // ignore if missing
            }

            // re-create composite index
            try {
                $table->index(['kiosk_code', 'transaction_id']);
            } catch (\Exception $e) {
                // ignore if already exists
            }
        });
    }
}
