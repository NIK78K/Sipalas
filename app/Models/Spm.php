<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Spm extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'satker_id',
        'akun_belanja_id',
        'tahun',
        'bulan',
        'nomor',
        'tanggal_pemeriksaan',
        'jenis',
        'nilai_transaksi',
        'catatan_pemeriksa',
        'link_bukti',
        'version',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'tahun' => 'integer',
            'bulan' => 'integer',
            'nilai_transaksi' => 'integer',
            'version' => 'integer',
            'tanggal_pemeriksaan' => 'date',
        ];
    }

    protected $appends = [
        'checked_count',
        'total_docs_count',
        'is_lengkap',
        'status_label',
    ];

    public function satker(): BelongsTo
    {
        return $this->belongsTo(Satker::class);
    }

    public function akunBelanja(): BelongsTo
    {
        return $this->belongsTo(AkunBelanja::class);
    }

    public function checklists(): HasMany
    {
        return $this->hasMany(SpmChecklist::class);
    }

    public function catatans(): HasMany
    {
        return $this->hasMany(SpmCatatan::class)
            ->whereNull('parent_id')
            ->oldest();
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /**
     * Get number of checked items.
     */
    public function getCheckedCountAttribute(): int
    {
        $documentIds = $this->activeDocumentIds();

        if ($this->relationLoaded('checklists')) {
            return $this->checklists
                ->whereIn('dokumen_wajib_id', $documentIds)
                ->where('status', 'sesuai')
                ->count();
        }

        return $this->checklists()
            ->whereIn('dokumen_wajib_id', $documentIds)
            ->where('status', 'sesuai')
            ->count();
    }

    /**
     * Get total active documents for this account.
     */
    public function getTotalDocsCountAttribute(): int
    {
        $documentIds = $this->activeDocumentIds();

        if ($this->relationLoaded('checklists')) {
            return count(array_filter($documentIds, fn (int $id) => $this->checklists
                ->firstWhere('dokumen_wajib_id', $id)?->status !== '-'));
        }

        $excludedCount = $this->checklists()
            ->whereIn('dokumen_wajib_id', $documentIds)
            ->where('status', '-')
            ->count();

        return count($documentIds) - $excludedCount;
    }

    /**
     * Determine if all mandatory documents are checked.
     */
    public function getIsLengkapAttribute(): bool
    {
        $total = $this->total_docs_count;

        return $total > 0 && $this->checked_count >= $total;
    }

    /**
     * Formatted status label: 'Lengkap' or 'Kurang n'.
     */
    public function getStatusLabelAttribute(): string
    {
        $total = $this->total_docs_count;
        $checked = $this->checked_count;

        if ($total > 0 && $checked >= $total) {
            return 'Lengkap';
        }

        $kurang = max(0, $total - $checked);

        return "Kurang {$kurang}";
    }

    /**
     * Initialize or synchronize checklist items based on akun belanja's active documents.
     */
    public function syncChecklists(): void
    {
        if (! $this->akunBelanja) {
            return;
        }

        $activeDocs = $this->akunBelanja->activeDokumenWajibs()->get();

        foreach ($activeDocs as $doc) {
            SpmChecklist::firstOrCreate(
                [
                    'spm_id' => $this->id,
                    'dokumen_wajib_id' => $doc->id,
                ],
                [
                    'status' => null,
                    'is_checked' => false,
                ]
            );
        }
    }

    /**
     * @return array<int, int>
     */
    private function activeDocumentIds(): array
    {
        if ($this->relationLoaded('akunBelanja') && $this->akunBelanja?->relationLoaded('dokumenWajibs')) {
            return $this->akunBelanja->dokumenWajibs
                ->where('is_active', true)
                ->modelKeys();
        }

        return $this->akunBelanja?->activeDokumenWajibs()->pluck('id')->all() ?? [];
    }
}
