<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('field_report_photos', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('field_report_id');
            $table->string('file_path');
            $table->text('file_url');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->timestamps();

            $table->foreign('field_report_id')->references('id')->on('field_reports')->onDelete('cascade');
            $table->index('field_report_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('field_report_photos');
    }
};
