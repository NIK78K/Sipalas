<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Satker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SatkerController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Satker::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('kode', 'like', "%{$search}%")
                    ->orWhere('nama', 'like', "%{$search}%");
            });
        }

        $satkers = $query->orderBy('kode')->paginate(20)->withQueryString();

        return Inertia::render('Master/Satker/Index', [
            'satkers' => $satkers,
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:10', Rule::unique(Satker::class)],
            'nama' => ['required', 'string', 'max:255', Rule::unique(Satker::class)],
        ], [
            'nama.unique' => 'Nama satker sudah terdaftar.',
        ]);

        Satker::create($validated);

        return back()->with('success', 'Satker berhasil ditambahkan.');
    }

    public function update(Request $request, Satker $satker): RedirectResponse
    {
        $validated = $request->validate([
            'kode' => ['required', 'string', 'max:10', Rule::unique(Satker::class)->ignore($satker->id)],
            'nama' => ['required', 'string', 'max:255', Rule::unique(Satker::class)->ignore($satker->id)],
        ], [
            'nama.unique' => 'Nama satker sudah terdaftar.',
        ]);

        $satker->update($validated);

        return back()->with('success', 'Satker berhasil diperbarui.');
    }

    public function destroy(Satker $satker): RedirectResponse
    {
        $satker->delete();

        return back()->with('success', 'Satker berhasil dihapus.');
    }
}
