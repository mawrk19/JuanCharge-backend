<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('lgus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 255);
            $table->string('region', 255)->nullable();
            $table->string('province', 255)->nullable();
            $table->string('city_municipality', 255)->nullable();
            $table->string('barangay', 255)->nullable();
            $table->text('address')->nullable();
            $table->string('contact_person', 255)->nullable();
            $table->string('contact_email', 255)->nullable();
            $table->string('contact_number', 50)->nullable();
            $table->enum('status', ['active', 'inactive', 'pending'])->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('lgus');
    }
};
