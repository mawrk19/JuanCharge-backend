<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (!Schema::hasColumn('kiosks', 'last_serviced_at')) {
                $table->timestamp('last_serviced_at')->nullable()->after('last_active');
                $table->index('last_serviced_at');
            }
        });
    }

    public function down(): void
    {
        Schema::table('kiosks', function (Blueprint $table) {
            if (Schema::hasColumn('kiosks', 'last_serviced_at')) {
                $table->dropIndex(['last_serviced_at']);
                $table->dropColumn('last_serviced_at');
            }
        });
    }
};
