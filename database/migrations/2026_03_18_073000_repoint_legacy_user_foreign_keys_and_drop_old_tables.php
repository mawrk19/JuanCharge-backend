<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->repointKioskAssignedToForeignKey();
        $this->repointLegacyUserForeignKeys();

        // Drop legacy tables after all references are removed.
        Schema::dropIfExists('lgu_users');
        Schema::dropIfExists('kiosk_users');
    }

    public function down(): void
    {
        // Intentionally irreversible: this migration deprecates legacy tables.
    }

    private function repointKioskAssignedToForeignKey(): void
    {
        if (!Schema::hasTable('kiosks') || !Schema::hasColumn('kiosks', 'assigned_to')) {
            return;
        }

        if (Schema::hasTable('lgu_users')) {
            DB::statement(
                'UPDATE kiosks k
                 INNER JOIN lgu_users lu ON lu.id = k.assigned_to
                 INNER JOIN users u ON u.email = lu.email
                 SET k.assigned_to = u.id
                 WHERE k.assigned_to IS NOT NULL'
            );
        }

        $this->dropForeignByColumn('kiosks', 'assigned_to');

        Schema::table('kiosks', function (Blueprint $table) {
            $table->foreign('assigned_to')->references('id')->on('users')->onDelete('set null');
        });
    }

    private function repointLegacyUserForeignKeys(): void
    {
        $targets = [
            ['table' => 'charging_sessions', 'column' => 'user_id', 'onDelete' => 'cascade'],
            ['table' => 'points_transactions', 'column' => 'user_id', 'onDelete' => 'cascade'],
            ['table' => 'recycling_logs', 'column' => 'user_id', 'onDelete' => 'cascade'],
            ['table' => 'port_activations', 'column' => 'user_id', 'onDelete' => 'cascade'],
            ['table' => 'point_vouchers', 'column' => 'claimed_by', 'onDelete' => 'set null'],
        ];

        foreach ($targets as $target) {
            $table = $target['table'];
            $column = $target['column'];

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            if (Schema::hasTable('kiosk_users')) {
                DB::statement(
                    "UPDATE {$table} t
                     INNER JOIN kiosk_users ku ON ku.id = t.{$column}
                     INNER JOIN users u ON u.email = ku.email
                     SET t.{$column} = u.id
                     WHERE t.{$column} IS NOT NULL"
                );
            }

            $this->dropForeignByColumn($table, $column);

            Schema::table($table, function (Blueprint $tableBlueprint) use ($column, $target) {
                $foreign = $tableBlueprint->foreign($column)->references('id')->on('users');
                if ($target['onDelete'] === 'set null') {
                    $foreign->nullOnDelete();
                } else {
                    $foreign->onDelete('cascade');
                }
            });
        }
    }

    private function dropForeignByColumn(string $table, string $column): void
    {
        $database = DB::getDatabaseName();

        $constraints = DB::table('information_schema.KEY_COLUMN_USAGE')
            ->select('CONSTRAINT_NAME')
            ->where('TABLE_SCHEMA', $database)
            ->where('TABLE_NAME', $table)
            ->where('COLUMN_NAME', $column)
            ->whereNotNull('REFERENCED_TABLE_NAME')
            ->pluck('CONSTRAINT_NAME');

        foreach ($constraints as $constraintName) {
            DB::statement("ALTER TABLE {$table} DROP FOREIGN KEY {$constraintName}");
        }
    }
};
