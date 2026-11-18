<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessCatatanPemeriksaImport;
use App\Models\ActivityLog;
use App\Models\ImportLog;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ImportController extends Controller
{
    public function index(): Response
    {
        $logs = ImportLog::with('user')
            ->latest('id')
            ->paginate(10);

        return Inertia::render('Import/Index', [
            'logs' => $logs,
        ]);
    }

    public function uploadPemeriksa(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx', 'max:20480'],
        ]);

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();

        $path = $file->store('imports', 'local');
        $fullPath = Storage::disk('local')->path($path);

        $log = ImportLog::create([
            'user_id' => $request->user()?->id,
            'tipe' => 'catatan_pemeriksa',
            'nama_file' => $originalName,
            'status' => 'pending',
            'berhasil' => 0,
            'dilewati' => 0,
        ]);

        ProcessCatatanPemeriksaImport::dispatch($log->id, $fullPath);

        ActivityLog::record('impor_excel', $log, [
            'nama_file' => $originalName,
            'tipe' => 'catatan_pemeriksa',
        ]);

        return back()->with('success', 'File Catatan Pemeriksa berhasil diunggah dan sedang diproses dalam antrean.');
    }

    public function status(ImportLog $import): JsonResponse
    {
        return response()->json([
            'id' => $import->id,
            'status' => $import->status,
            'berhasil' => $import->berhasil,
            'dilewati' => $import->dilewati,
            'laporan' => $import->laporan,
            'updated_at' => $import->updated_at->toISOString(),
        ]);
    }
}
