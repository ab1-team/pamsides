<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('master_arus_kas', function (Blueprint $table) {
            $table->id();
            $table->string('nama_akun', 150)->nullable(); 
            $table->string('debit', 15)->nullable();      
            $table->string('kredit', 15)->nullable();    
            $table->integer('parent_id')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('master_arus_kas');
    }
};
