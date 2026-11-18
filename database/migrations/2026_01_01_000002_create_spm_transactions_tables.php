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
        Schema::create('spms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->foreignId('akun_belanja_id')->constrained('akun_belanjas');
            $table->unsignedSmallInteger('tahun');
            $table->unsignedTinyInteger('bulan'); // 1 - 12
            $table->string('nomor', 50);
            $table->date('tanggal_pemeriksaan')->nullable();
            $table->string('jenis', 20)->default('LS');
            $table->unsignedBigInteger('nilai_transaksi')->default(0);
            $table->text('catatan_pemeriksa')->nullable();
            $table->text('link_bukti')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->softDeletes();
            $table->timestamps();

            $table->unique(['satker_id', 'tahun', 'bulan', 'nomor']);
            $table->index(['satker_id', 'tahun', 'bulan']);
            $table->index('akun_belanja_id');
        });

        Schema::create('spm_checklists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spm_id')->constrained('spms')->cascadeOnDelete();
            $table->foreignId('dokumen_wajib_id')->constrained('dokumen_wajibs')->cascadeOnDelete();
            $table->boolean('is_checked')->default(false);
            $table->foreignId('checked_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('checked_at')->nullable();
            $table->timestamps();

            $table->unique(['spm_id', 'dokumen_wajib_id']);
            $table->index(['dokumen_wajib_id', 'is_checked']);
        });

        Schema::create('spm_catatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('spm_id')->constrained('spms')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->text('isi');
            $table->timestamps();
        });

        Schema::create('satker_catatans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('satker_id')->constrained('satkers')->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->date('tanggal');
            $table->text('isi');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('satker_catatans');
        Schema::dropIfExists('spm_catatans');
        Schema::dropIfExists('spm_checklists');
        Schema::dropIfExists('spms');
    }
};
