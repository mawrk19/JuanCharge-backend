<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class MakeKioskFieldsNullable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::statement('ALTER TABLE kiosks MODIFY serial_number VARCHAR(255) NULL');
        DB::statement('ALTER TABLE kiosks MODIFY mac_address VARCHAR(255) NULL');
        DB::statement('ALTER TABLE kiosks MODIFY ip_address VARCHAR(255) NULL');
        DB::statement('ALTER TABLE kiosks MODIFY software_version VARCHAR(255) NULL');
        DB::statement('ALTER TABLE kiosks MODIFY notes TEXT NULL');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::statement('ALTER TABLE kiosks MODIFY serial_number VARCHAR(255) NOT NULL');
    }
}
