<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DokumenWajibController extends Controller
{
    public function store(Request $request, AkunBelanja $akun): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
        ]);

        $maxUrutan = (int) $akun->dokumenWajibs()->max('urutan');

        $akun->dokumenWajibs()->create([
            'nama' => $validated['nama'],
            'urutan' => $maxUrutan + 1,
            'is_active' => true,
        ]);

        return back()->with('success', 'Dokumen wajib berhasil ditambahkan.');
    }

    public function update(Request $request, DokumenWajib $dokumen): RedirectResponse
    {
        $validated = $request->validate([
            'nama' => ['required', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
        ]);

        $dokumen->update($validated);

        return back()->with('success', 'Dokumen wajib berhasil diperbarui.');
    }

    public function toggle(DokumenWajib $dokumen): RedirectResponse
    {
        $dokumen->update(['is_active' => ! $dokumen->is_active]);

        $statusText = $dokumen->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Dokumen {$dokumen->nama} berhasil {$statusText}.");
    }

    public function destroy(DokumenWajib $dokumen): RedirectResponse
    {
        // FR-07: Soft delete agar riwayat pemeriksaan tidak hilang
        $dokumen->delete();

        return back()->with('success', 'Dokumen wajib berhasil dinonaktifkan (dihapus).');
    }

    public function reorder(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array'],
            'ids.*' => ['integer', 'exists:dokumen_wajibs,id'],
        ]);

        DB::transaction(function () use ($validated) {
            foreach ($validated['ids'] as $index => $id) {
                DokumenWajib::where('id', $id)->update(['urutan' => $index + 1]);
            }
        });

        return back()->with('success', 'Urutan dokumen berhasil diperbarui.');
    }
}
