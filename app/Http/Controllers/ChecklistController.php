<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Spm;
use App\Models\SpmChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ChecklistController extends Controller
{
    /**
     * Atomically update SPM checklist and official examiner notes.
     */
    public function update(Request $request, Spm $spm): RedirectResponse
    {
        Gate::authorize('examine', $spm);

        $validated = $request->validate([
            'version' => ['required', 'integer'],
            'statuses' => ['required', 'array'],
            'statuses.*' => ['nullable', Rule::in(['sesuai', 'tidak_sesuai', '-'])],
            'catatan_pemeriksa' => ['nullable', 'string'],
        ]);

        // Optimistic Locking Check
        if ((int) $validated['version'] !== (int) $spm->version) {
            return back()->withErrors([
                'version' => 'Terjadi konflik data! SPM telah diperbarui oleh pengguna lain. Silakan refresh halaman.',
            ]);
        }

        $userId = $request->user()?->id;
        $statuses = $validated['statuses'];

        DB::transaction(function () use ($spm, $statuses, $validated, $userId) {
            // Ensure all active documents for this account exist in spm_checklists
            $spm->syncChecklists();

            $allChecklists = $spm->checklists()->get();
            $allowedIds = $allChecklists->pluck('dokumen_wajib_id')->map(fn ($id) => (int) $id)->all();
            $submittedIds = array_map('intval', array_keys($statuses));

            if (array_diff($submittedIds, $allowedIds)) {
                throw ValidationException::withMessages([
                    'statuses' => 'Status dokumen tidak sesuai dengan checklist SPM ini.',
                ]);
            }

            foreach ($allChecklists as $chk) {
                $key = (string) $chk->dokumen_wajib_id;
                if (! array_key_exists($key, $statuses)) {
                    continue;
                }

                $status = $statuses[$key] ?: null;
                if ($chk->status !== $status) {
                    $chk->update([
                        'status' => $status,
                        'is_checked' => $status === 'sesuai',
                        'checked_by' => $status ? $userId : null,
                        'checked_at' => $status ? now() : null,
                    ]);
                }
            }

            // Update catatan pemeriksa & bump version
            $spm->update([
                'catatan_pemeriksa' => $validated['catatan_pemeriksa'] ?? $spm->catatan_pemeriksa,
                'updated_by' => $userId,
                'version' => $spm->version + 1,
            ]);
        });

        ActivityLog::record('perubahan_checklist', $spm, [
            'nomor' => $spm->nomor,
            'checked_count' => count(array_filter($statuses, fn ($status) => $status === 'sesuai')),
            'catatan_pemeriksa' => $validated['catatan_pemeriksa'] ?? null,
        ]);

        return back()->with('success', 'Checklist kelengkapan berhasil disimpan.');
    }
}
