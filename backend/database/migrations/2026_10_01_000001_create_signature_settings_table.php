<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('signature_settings', function (Blueprint $table) {
            $table->id();
            $table->string('report_key', 60)->unique();
            $table->string('label', 120);
            $table->longText('template_html')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_mime', 30)->nullable();
            $table->unsignedInteger('image_size')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('signature_settings');
    }
};