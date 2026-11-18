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
        Schema::create('import_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('tipe', 50)->default('catatan_pemeriksa');
            $table->string('nama_file');
            $table->string('status', 20)->default('pending'); // pending, processing, completed, failed
            $table->unsignedInteger('berhasil')->default(0);
            $table->unsignedInteger('dilewati')->default(0);
            $table->json('laporan')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('import_logs');
    }
};
