<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (!Schema::hasColumn('kiosks', 'collection_schedule_id')) {
                $table->unsignedBigInteger('collection_schedule_id')->nullable()->after('lgu_id');
                $table->foreign('collection_schedule_id')->references('id')->on('collection_schedules')->onDelete('set null');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (Schema::hasColumn('kiosks', 'collection_schedule_id')) {
                $table->dropForeign(['collection_schedule_id']);
                $table->dropColumn('collection_schedule_id');
            }
        });
    }
};
