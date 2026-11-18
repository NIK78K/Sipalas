<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])->name('dashboard');

    // List Kelengkapan Rekapitulasi (FR-17, FR-18)
    Route::get('/kelengkapan', [\App\Http\Controllers\KelengkapanController::class, 'index'])->name('kelengkapan.index');
    Route::get('/kelengkapan/{akun}', [\App\Http\Controllers\KelengkapanController::class, 'show'])->name('kelengkapan.show');

    // Ekspor Excel (FR-28)
    Route::get('/ekspor/spm', [\App\Http\Controllers\ExportController::class, 'exportSpm'])->name('ekspor.spm');
    Route::get('/ekspor/kelengkapan', [\App\Http\Controllers\ExportController::class, 'exportKelengkapan'])->name('ekspor.kelengkapan');

    Route::middleware('role:operator')->group(function () {
        Route::get('/pengguna', [UserController::class, 'index'])->name('users.index');
        Route::post('/pengguna', [UserController::class, 'store'])->name('users.store');
        Route::put('/pengguna/{user}', [UserController::class, 'update'])->name('users.update');
        Route::patch('/pengguna/{user}/toggle', [UserController::class, 'toggle'])->name('users.toggle');
        Route::post('/pengguna/{user}/reset-password', [UserController::class, 'resetPassword'])->name('users.reset-password');
        Route::delete('/pengguna/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('role:operator,admin')->prefix('master')->name('master.')->group(function () {
        // Master Satker
        Route::get('/satker', [\App\Http\Controllers\Master\SatkerController::class, 'index'])->name('satker.index');
        Route::post('/satker', [\App\Http\Controllers\Master\SatkerController::class, 'store'])->name('satker.store');
        Route::put('/satker/{satker}', [\App\Http\Controllers\Master\SatkerController::class, 'update'])->name('satker.update');
        Route::delete('/satker/{satker}', [\App\Http\Controllers\Master\SatkerController::class, 'destroy'])->name('satker.destroy');

        // Master Akun Belanja
        Route::get('/akun', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'index'])->name('akun.index');
        Route::post('/akun', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'store'])->name('akun.store');
        Route::get('/akun/{akun}', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'show'])->name('akun.show');
        Route::put('/akun/{akun}', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'update'])->name('akun.update');
        Route::patch('/akun/{akun}/toggle', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'toggle'])->name('akun.toggle');
        Route::delete('/akun/{akun}', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'destroy'])->name('akun.destroy');
        Route::post('/akun/impor-kelengkapan', [\App\Http\Controllers\Master\AkunBelanjaController::class, 'import'])->name('akun.import');

        // Dokumen Wajib
        Route::post('/akun/{akun}/dokumen', [\App\Http\Controllers\Master\DokumenWajibController::class, 'store'])->name('dokumen.store');
        Route::put('/dokumen/{dokumen}', [\App\Http\Controllers\Master\DokumenWajibController::class, 'update'])->name('dokumen.update');
        Route::patch('/dokumen/{dokumen}/toggle', [\App\Http\Controllers\Master\DokumenWajibController::class, 'toggle'])->name('dokumen.toggle');
        Route::delete('/dokumen/{dokumen}', [\App\Http\Controllers\Master\DokumenWajibController::class, 'destroy'])->name('dokumen.destroy');
        Route::post('/dokumen/reorder', [\App\Http\Controllers\Master\DokumenWajibController::class, 'reorder'])->name('dokumen.reorder');
    });

    // SPM Transaksi & Checklist
    Route::get('/spm', [\App\Http\Controllers\SpmController::class, 'index'])->name('spm.index');
    Route::get('/spm/create', [\App\Http\Controllers\SpmController::class, 'create'])->name('spm.create');
    Route::post('/spm', [\App\Http\Controllers\SpmController::class, 'store'])->name('spm.store');
    Route::get('/spm/{spm}', [\App\Http\Controllers\SpmController::class, 'show'])->name('spm.show');
    Route::get('/spm/{spm}/edit', [\App\Http\Controllers\SpmController::class, 'edit'])->name('spm.edit');
    Route::put('/spm/{spm}', [\App\Http\Controllers\SpmController::class, 'update'])->name('spm.update');
    Route::delete('/spm/{spm}', [\App\Http\Controllers\SpmController::class, 'destroy'])->name('spm.destroy');

    // Checklist Simpan
    Route::post('/spm/{spm}/checklist', [\App\Http\Controllers\ChecklistController::class, 'update'])->name('spm.checklist.update');

    // Catatan Tambahan SPM
    Route::post('/spm/{spm}/catatan', [\App\Http\Controllers\SpmCatatanController::class, 'store'])->name('spm.catatan.store');
    Route::delete('/spm-catatan/{catatan}', [\App\Http\Controllers\SpmCatatanController::class, 'destroy'])->name('spm.catatan.destroy');

    // Catatan Pengawasan Satker (MonPPSPM)
    Route::get('/catatan-satker', [\App\Http\Controllers\SatkerCatatanController::class, 'index'])->name('satker.catatan.index');
    Route::post('/satker/{satker}/catatan', [\App\Http\Controllers\SatkerCatatanController::class, 'store'])->name('satker.catatan.store');
    Route::delete('/catatan-satker/{catatan}', [\App\Http\Controllers\SatkerCatatanController::class, 'destroy'])->name('satker.catatan.destroy');

    // Impor Data Excel (FR-25, FR-26) & Log Aktivitas (FR-29)
    Route::middleware('role:operator,admin')->group(function () {
        Route::get('/impor', [\App\Http\Controllers\ImportController::class, 'index'])->name('impor.index');
        Route::post('/impor/pemeriksa', [\App\Http\Controllers\ImportController::class, 'uploadPemeriksa'])->name('impor.pemeriksa');
        Route::get('/impor/status/{import}', [\App\Http\Controllers\ImportController::class, 'status'])->name('impor.status');

        Route::get('/log', [\App\Http\Controllers\ActivityLogController::class, 'index'])->name('log.index');
    });
});

require __DIR__.'/settings.php';

