<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\AkunBelanja;
use App\Services\Excel\ListKelengkapanParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AkunBelanjaController extends Controller
{
    public function index(Request $request): Response
    {
        $query = AkunBelanja::query()->withCount([
            'dokumenWajibs as dokumen_count',
            'dokumenWajibs as active_dokumen_count' => function ($q) {
                $q->where('is_active', true);
            },
        ]);

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->input('status') === '1');
        }

        $accounts = $query->orderBy('kode')->orderBy('nama')->paginate(15)->withQueryString();

        return Inertia::render('Master/AkunBelanja/Index', [
            'accounts' => $accounts,
            'filters' => $request->only(['search', 'status']),
        ]);
    }

    public function show(AkunBelanja $akun): Response
    {
        $akun->load([
            'dokumenWajibs' => function ($q) {
                $q->orderBy('urutan');
            },
        ]);

        return Inertia::render('Master/AkunBelanja/Show', [
            'account' => $akun,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255', Rule::unique(AkunBelanja::class)],
            'is_active' => ['boolean'],
        ]);

        $validated['is_active'] = $validated['is_active'] ?? true;

        AkunBelanja::create($validated);

        return back()->with('success', 'Akun belanja berhasil ditambahkan.');
    }

    public function update(Request $request, AkunBelanja $akun): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:20'],
            'nama' => ['required', 'string', 'max:255', Rule::unique(AkunBelanja::class)->ignore($akun->id)],
            'is_active' => ['required', 'boolean'],
        ]);

        $akun->update($validated);

        return back()->with('success', 'Akun belanja berhasil diperbarui.');
    }

    public function toggle(AkunBelanja $akun): RedirectResponse
    {
        $akun->update(['is_active' => ! $akun->is_active]);

        $statusText = $akun->is_active ? 'diaktifkan' : 'dinonaktifkan';

        return back()->with('success', "Akun belanja {$akun->nama} berhasil {$statusText}.");
    }

    public function destroy(AkunBelanja $akun): RedirectResponse
    {
        $akun->delete();

        return back()->with('success', 'Akun belanja berhasil dihapus.');
    }

    public function import(Request $request, ListKelengkapanParser $parser): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls', 'max:20480'],
        ]);

        $file = $request->file('file');
        $result = $parser->import($file->getRealPath());

        return back()->with('success', "Berhasil mengimpor {$result['accounts']} akun belanja dan {$result['documents']} dokumen wajib.");
    }
}
