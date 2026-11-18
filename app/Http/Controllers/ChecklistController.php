<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Spm;
use App\Models\SpmChecklist;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

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
            'checked_ids' => ['present', 'array'],
            'checked_ids.*' => ['integer', 'exists:dokumen_wajibs,id'],
            'catatan_pemeriksa' => ['nullable', 'string'],
        ]);

        // Optimistic Locking Check
        if ((int) $validated['version'] !== (int) $spm->version) {
            return back()->withErrors([
                'version' => 'Terjadi konflik data! SPM telah diperbarui oleh pengguna lain. Silakan refresh halaman.',
            ]);
        }

        $userId = $request->user()?->id;
        $checkedDocIds = array_map('intval', $validated['checked_ids']);

        DB::transaction(function () use ($spm, $checkedDocIds, $validated, $userId) {
            // Ensure all active documents for this account exist in spm_checklists
            $spm->syncChecklists();

            $allChecklists = $spm->checklists()->get();

            foreach ($allChecklists as $chk) {
                $shouldBeChecked = in_array((int) $chk->dokumen_wajib_id, $checkedDocIds, true);

                if ($chk->is_checked !== $shouldBeChecked) {
                    $chk->update([
                        'is_checked' => $shouldBeChecked,
                        'checked_by' => $shouldBeChecked ? $userId : null,
                        'checked_at' => $shouldBeChecked ? now() : null,
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
            'checked_count' => count($checkedDocIds),
            'catatan_pemeriksa' => $validated['catatan_pemeriksa'] ?? null,
        ]);

        return back()->with('success', 'Checklist kelengkapan berhasil disimpan.');
    }
}
