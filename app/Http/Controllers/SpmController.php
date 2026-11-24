<?php

namespace App\Http\Controllers;

use App\Models\AkunBelanja;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class SpmController extends Controller
{
    public function index(Request $request): Response
    {
        Gate::authorize('viewAny', Spm::class);

        $query = Spm::query()->with([
            'satker',
            'akunBelanja.dokumenWajibs',
            'checklists',
        ]);

        if ($search = $request->input('search')) {
            $query->where('nomor', 'like', "%{$search}%");
        }

        if ($satkerId = $request->input('satker_id')) {
            $query->where('satker_id', $satkerId);
        }

        if ($tahun = $request->input('tahun')) {
            $query->where('tahun', $tahun);
        }

        if ($bulan = $request->input('bulan')) {
            $query->where('bulan', $bulan);
        }

        if ($jenis = $request->input('jenis')) {
            $query->where('jenis', $jenis);
        }

        if ($akunId = $request->input('akun_belanja_id')) {
            $query->where('akun_belanja_id', $akunId);
        }

        // Status filter: 'lengkap' or 'kurang'
        if ($status = $request->input('status')) {
            if ($status === 'lengkap') {
                // SPM where checked count == total active docs count
                $query->whereHas('checklists', function ($q) {
                    $q->where('is_checked', true);
                });
            }
        }

        $spms = $query->latest('id')->paginate(15)->withQueryString();

        // If client-side status filter 'lengkap' / 'kurang' applied on paginator
        if ($request->input('status') === 'lengkap') {
            $filtered = $spms->getCollection()->filter(fn ($item) => $item->is_lengkap);
            $spms->setCollection($filtered->values());
        } elseif ($request->input('status') === 'kurang') {
            $filtered = $spms->getCollection()->filter(fn ($item) => ! $item->is_lengkap);
            $spms->setCollection($filtered->values());
        }

        return Inertia::render('Spm/Index', [
            'spms' => $spms,
            'satkers' => Satker::orderBy('kode')->get(['id', 'kode', 'nama']),
            'accounts' => AkunBelanja::where('is_active', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
            'filters' => $request->only(['search', 'satker_id', 'tahun', 'bulan', 'jenis', 'akun_belanja_id', 'status']),
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('create', Spm::class);

        return Inertia::render('Spm/Form', [
            'spm' => null,
            'satkers' => Satker::orderBy('kode')->get(['id', 'kode', 'nama']),
            'accounts' => AkunBelanja::where('is_active', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('create', Spm::class);

        $validated = $request->validate([
            'satker_id' => ['required', 'exists:satkers,id'],
            'akun_belanja_id' => ['required', 'exists:akun_belanjas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'nomor' => [
                'required',
                'string',
                'max:50',
                Rule::unique('spms')->where(function ($query) use ($request) {
                    return $query->where('satker_id', $request->satker_id)
                        ->where('tahun', $request->tahun)
                        ->where('bulan', $request->bulan);
                }),
            ],
            'tanggal_pemeriksaan' => ['nullable', 'date'],
            'jenis' => ['required', 'string', 'max:20'],
            'nilai_transaksi' => ['required', 'integer', 'min:0'],
            'catatan_pemeriksa' => ['nullable', 'string'],
            'link_bukti' => ['nullable', 'string', 'max:1000'],
        ], [
            'nomor.unique' => 'Nomor SPM sudah terdaftar untuk satker, tahun, dan bulan yang sama.',
        ]);

        $validated['created_by'] = Auth::id();
        $validated['updated_by'] = Auth::id();
        $validated['version'] = 1;

        $spm = Spm::create($validated);
        $spm->syncChecklists();

        return redirect()->route('spm.show', $spm)->with('success', 'Data SPM berhasil dibuat.');
    }

    public function show(Request $request, Spm $spm): Response
    {
        Gate::authorize('view', $spm);

        $spm->load([
            'satker',
            'akunBelanja.dokumenWajibs',
            'checklists.dokumenWajib' => function ($q) {
                $q->orderBy('urutan');
            },
            'catatans.user',
            'catatans.replies.user',
            'creator',
            'updater',
        ]);

        // Auto-sync if new documents exist
        $spm->syncChecklists();

        // Sort checklists by document order
        $checklists = $spm->checklists->sortBy(function ($item) {
            return $item->dokumenWajib ? $item->dokumenWajib->urutan : 999;
        })->values();

        /** @var User|null $currentUser */
        $currentUser = $request->user();

        return Inertia::render('Spm/Show', [
            'spm' => $spm,
            'checklists' => $checklists,
            'catatans' => $spm->catatans,
            'canExamine' => $currentUser?->canExamine() ?? false,
        ]);
    }

    public function edit(Spm $spm): Response
    {
        Gate::authorize('update', $spm);

        return Inertia::render('Spm/Form', [
            'spm' => $spm,
            'satkers' => Satker::orderBy('kode')->get(['id', 'kode', 'nama']),
            'accounts' => AkunBelanja::where('is_active', true)->orderBy('nama')->get(['id', 'kode', 'nama']),
        ]);
    }

    public function update(Request $request, Spm $spm): RedirectResponse
    {
        Gate::authorize('update', $spm);

        // Optimistic Locking Check
        if ($request->filled('version') && (int) $request->input('version') !== (int) $spm->version) {
            return back()->withErrors([
                'version' => 'Data SPM ini telah diubah oleh pengguna lain. Silakan muat ulang halaman sebelum menyimpan kembali.',
            ]);
        }

        $validated = $request->validate([
            'satker_id' => ['required', 'exists:satkers,id'],
            'akun_belanja_id' => ['required', 'exists:akun_belanjas,id'],
            'tahun' => ['required', 'integer', 'min:2020', 'max:2099'],
            'bulan' => ['required', 'integer', 'between:1,12'],
            'nomor' => [
                'required',
                'string',
                'max:50',
                Rule::unique('spms')->where(function ($query) use ($request) {
                    return $query->where('satker_id', $request->satker_id)
                        ->where('tahun', $request->tahun)
                        ->where('bulan', $request->bulan);
                })->ignore($spm->id),
            ],
            'tanggal_pemeriksaan' => ['nullable', 'date'],
            'jenis' => ['required', 'string', 'max:20'],
            'nilai_transaksi' => ['required', 'integer', 'min:0'],
            'catatan_pemeriksa' => ['nullable', 'string'],
            'link_bukti' => ['nullable', 'string', 'max:1000'],
        ], [
            'nomor.unique' => 'Nomor SPM sudah terdaftar untuk satker, tahun, dan bulan yang sama.',
        ]);

        $accountChanged = (int) $spm->akun_belanja_id !== (int) $validated['akun_belanja_id'];

        $validated['updated_by'] = $request->user()?->id;
        $validated['version'] = $spm->version + 1;

        $spm->update($validated);

        if ($accountChanged) {
            $spm->syncChecklists();
        }

        return redirect()->route('spm.show', $spm)->with('success', 'Data SPM berhasil diperbarui.');
    }

    public function destroy(Spm $spm): RedirectResponse
    {
        Gate::authorize('delete', $spm);

        \App\Models\ActivityLog::record('penghapusan_spm', $spm, [
            'nomor' => $spm->nomor,
            'satker_id' => $spm->satker_id,
            'tahun' => $spm->tahun,
            'bulan' => $spm->bulan,
        ]);

        $spm->delete();

        return redirect()->route('spm.index')->with('success', 'Data SPM berhasil dihapus.');
    }
}
