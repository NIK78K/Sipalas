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
        Schema::create('satkers', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 10)->unique();
            $table->string('nama');
            $table->timestamps();
        });

        Schema::create('akun_belanjas', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20);
            $table->string('nama')->unique();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('dokumen_wajibs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('akun_belanja_id')->constrained('akun_belanjas')->cascadeOnDelete();
            $table->string('nama');
            $table->unsignedInteger('urutan')->default(1);
            $table->boolean('is_active')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['akun_belanja_id', 'urutan']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dokumen_wajibs');
        Schema::dropIfExists('akun_belanjas');
        Schema::dropIfExists('satkers');
    }
};
