<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddKioskSyncFieldsToRecyclingLogs extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('recycling_logs', function (Blueprint $table) {
            // Add identifying columns for sync
            if (!Schema::hasColumn('recycling_logs', 'transaction_id')) {
                $table->string('transaction_id')->nullable()->after('id');
            }
            if (!Schema::hasColumn('recycling_logs', 'kiosk_code')) {
                $table->string('kiosk_code')->nullable()->after('transaction_id');
            }
            
            // Add composite index for efficient idempotency checks
            // We verify uniqueness based on kiosk_code + transaction_id
            $table->index(['kiosk_code', 'transaction_id']);
        });

        // Make weight_kg nullable since the kiosk might not provide it in this flow
        try {
            DB::statement('ALTER TABLE recycling_logs MODIFY weight_kg DECIMAL(8, 2) NULL');
        } catch (\Exception $e) {
            // Fallback or if already nullable
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
            $table->dropIndex(['kiosk_code', 'transaction_id']);
            $table->dropColumn(['transaction_id', 'kiosk_code']);
        });

        // We probably shouldn't revert weight_kg to NOT NULL as it might cause data loss 
        // if nulls were introduced.
    }
}
