<?php

namespace App\Http\Controllers;

use App\Models\Satker;
use App\Models\SatkerCatatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SatkerCatatanController extends Controller
{
    public function index(Request $request): Response
    {
        $satkers = Satker::orderBy('kode')->get(['id', 'kode', 'nama']);

        $selectedSatkerId = $request->input('satker_id') ?? $satkers->first()?->id;

        $catatans = [];
        if ($selectedSatkerId) {
            $catatans = SatkerCatatan::with('user')
                ->where('satker_id', $selectedSatkerId)
                ->latest('tanggal')
                ->latest('id')
                ->paginate(15)
                ->withQueryString();
        }

        return Inertia::render('Satker/Catatan', [
            'satkers' => $satkers,
            'selectedSatkerId' => (int) $selectedSatkerId,
            'catatans' => $catatans,
        ]);
    }

    public function store(Request $request, Satker $satker): RedirectResponse
    {
        $validated = $request->validate([
            'tanggal' => ['required', 'date'],
            'isi' => ['required', 'string', 'max:10000'],
        ]);

        $satker->catatans()->create([
            'user_id' => $request->user()?->id,
            'tanggal' => $validated['tanggal'],
            'isi' => $validated['isi'],
        ]);

        return back()->with('success', 'Catatan pengawasan satker berhasil disimpan.');
    }

    public function destroy(Request $request, SatkerCatatan $catatan): RedirectResponse
    {
        $user = $request->user();

        if (! $user->isOperator() && ! $user->isAdmin() && $user->id !== $catatan->user_id) {
            abort(403, 'Anda tidak berhak menghapus catatan ini.');
        }

        $catatan->delete();

        return back()->with('success', 'Catatan satker berhasil dihapus.');
    }
}
