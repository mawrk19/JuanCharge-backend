<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

class AddLguTechnicianRole extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        if (!DB::table('roles')->where('slug', 'lgu_technician')->exists()) {
            DB::table('roles')->insert([
                'name' => 'LGU Technician',
                'slug' => 'lgu_technician',
                'description' => 'LGU technician assigned to maintenance and field operations',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        DB::table('roles')->where('slug', 'lgu_technician')->delete();
    }
}
