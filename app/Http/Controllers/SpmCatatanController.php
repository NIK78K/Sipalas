<?php

namespace App\Http\Controllers;

use App\Models\Spm;
use App\Models\SpmCatatan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SpmCatatanController extends Controller
{
    public function store(Request $request, Spm $spm): RedirectResponse
    {
        Gate::authorize('create', SpmCatatan::class);

        $validated = $request->validate([
            'isi' => ['required', 'string', 'max:5000'],
            'parent_id' => [
                'nullable',
                'integer',
                \Illuminate\Validation\Rule::exists('spm_catatans', 'id')->where('spm_id', $spm->id),
            ],
        ]);

        $spm->catatans()->create([
            'user_id' => $request->user()?->id,
            'parent_id' => $validated['parent_id'] ?? null,
            'isi' => $validated['isi'],
        ]);

        return back()->with('success', 'Catatan tambahan berhasil ditambahkan.');
    }

    public function destroy(Request $request, SpmCatatan $catatan): RedirectResponse
    {
        Gate::authorize('delete', $catatan);

        $catatan->delete();

        return back()->with('success', 'Catatan tambahan berhasil dihapus.');
    }
}
