<?php

namespace App\Services;

use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\Satker;
use App\Models\Spm;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class RekapKelengkapanService
{
    /**
     * Get summary metrics per Satker for the dashboard.
     *
     * @return array{satkers: Collection<int, array<string, mixed>>, totals: array<string, mixed>}
     */
    public function getSatkerSummary(?int $tahun = null, ?int $bulan = null): array
    {
        $satkers = Satker::orderBy('kode')->get();

        $spmQuery = Spm::query()
            ->with(['akunBelanja.dokumenWajibs', 'checklists']);

        if ($tahun) {
            $spmQuery->where('tahun', $tahun);
        }

        if ($bulan) {
            $spmQuery->where('bulan', $bulan);
        }

        $allSpms = $spmQuery->get()->groupBy('satker_id');

        $result = [];
        $totalProvSpm = 0;
        $totalProvLengkap = 0;
        $totalProvKurang = 0;
        $totalProvNilai = 0;

        foreach ($satkers as $satker) {
            /** @var Collection<int, Spm> $spmList */
            $spmList = $allSpms->get($satker->id, collect());

            $totalSpm = $spmList->count();
            $lengkap = $spmList->filter(fn (Spm $spm) => $spm->is_lengkap)->count();
            $kurang = $totalSpm - $lengkap;
            $nilai = (int) $spmList->sum('nilai_transaksi');
            $persen = $totalSpm > 0 ? round(($lengkap / $totalSpm) * 100, 1) : 0;

            $result[] = [
                'satker_id' => $satker->id,
                'kode' => $satker->kode,
                'nama' => $satker->nama,
                'total_spm' => $totalSpm,
                'spm_lengkap' => $lengkap,
                'spm_kurang' => $kurang,
                'persen_lengkap' => $persen,
                'nilai_transaksi' => $nilai,
            ];

            $totalProvSpm += $totalSpm;
            $totalProvLengkap += $lengkap;
            $totalProvKurang += $kurang;
            $totalProvNilai += $nilai;
        }

        $overallPercent = $totalProvSpm > 0 ? round(($totalProvLengkap / $totalProvSpm) * 100, 1) : 0;

        return [
            'satkers' => collect($result),
            'totals' => [
                'total_spm' => $totalProvSpm,
                'total_lengkap' => $totalProvLengkap,
                'total_kurang' => $totalProvKurang,
                'total_nilai' => $totalProvNilai,
                'overall_percent' => $overallPercent,
            ],
        ];
    }

    /**
     * Get summary metrics per Akun Belanja (FR-17).
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function getAkunSummary(?int $satkerId = null, ?int $tahun = null, ?int $bulan = null): Collection
    {
        $akunList = AkunBelanja::where('is_active', true)
            ->with(['dokumenWajibs' => fn ($q) => $q->where('is_active', true)])
            ->orderBy('kode')
            ->orderBy('nama')
            ->get();

        $spmQuery = Spm::query()
            ->with(['akunBelanja.dokumenWajibs', 'checklists']);

        if ($satkerId) {
            $spmQuery->where('satker_id', $satkerId);
        }
        if ($tahun) {
            $spmQuery->where('tahun', $tahun);
        }
        if ($bulan) {
            $spmQuery->where('bulan', $bulan);
        }

        $allSpms = $spmQuery->get()->groupBy('akun_belanja_id');

        return $akunList->map(function (AkunBelanja $akun) use ($allSpms) {
            /** @var Collection<int, Spm> $spmList */
            $spmList = $allSpms->get($akun->id, collect());

            $totalSpm = $spmList->count();
            $lengkap = $spmList->filter(fn (Spm $spm) => $spm->is_lengkap)->count();
            $kurang = $totalSpm - $lengkap;
            $persen = $totalSpm > 0 ? round(($lengkap / $totalSpm) * 100, 1) : 0;
            $nilai = (int) $spmList->sum('nilai_transaksi');

            return [
                'id' => $akun->id,
                'kode' => $akun->kode,
                'nama' => $akun->nama,
                'total_docs' => $akun->dokumenWajibs->count(),
                'total_spm' => $totalSpm,
                'spm_lengkap' => $lengkap,
                'spm_kurang' => $kurang,
                'persen_lengkap' => $persen,
                'nilai_transaksi' => $nilai,
            ];
        });
    }

    /**
     * Get detail breakdown per Dokumen Wajib for a specific Akun Belanja (FR-18).
     *
     * @return array{account: AkunBelanja, total_spm: int, documents: Collection<int, array<string, mixed>>}
     */
    public function getAkunDetail(AkunBelanja $akun, ?int $satkerId = null, ?int $tahun = null, ?int $bulan = null): array
    {
        $spmQuery = Spm::where('akun_belanja_id', $akun->id)
            ->with(['satker', 'checklists']);

        if ($satkerId) {
            $spmQuery->where('satker_id', $satkerId);
        }
        if ($tahun) {
            $spmQuery->where('tahun', $tahun);
        }
        if ($bulan) {
            $spmQuery->where('bulan', $bulan);
        }

        $spms = $spmQuery->orderBy('satker_id')->orderBy('bulan')->orderBy('nomor')->get();
        $totalSpm = $spms->count();

        $docs = $akun->activeDokumenWajibs()->get();

        $documents = $docs->map(function (DokumenWajib $doc) use ($spms, $totalSpm) {
            $fulfilled = 0;
            $missingSpms = [];

            foreach ($spms as $spm) {
                $chk = $spm->checklists->firstWhere('dokumen_wajib_id', $doc->id);
                if ($chk && $chk->is_checked) {
                    $fulfilled++;
                } else {
                    $missingSpms[] = [
                        'id' => $spm->id,
                        'nomor' => $spm->nomor,
                        'satker_kode' => $spm->satker?->kode,
                        'satker_nama' => $spm->satker?->nama,
                        'tahun' => $spm->tahun,
                        'bulan' => $spm->bulan,
                        'jenis' => $spm->jenis,
                        'nilai_transaksi' => $spm->nilai_transaksi,
                    ];
                }
            }

            $percent = $totalSpm > 0 ? round(($fulfilled / $totalSpm) * 100, 1) : 0;

            return [
                'id' => $doc->id,
                'urutan' => $doc->urutan,
                'nama' => $doc->nama,
                'fulfilled_count' => $fulfilled,
                'unfulfilled_count' => $totalSpm - $fulfilled,
                'percent' => $percent,
                'missing_spms' => $missingSpms,
            ];
        });

        return [
            'account' => $akun,
            'total_spm' => $totalSpm,
            'documents' => $documents,
        ];
    }
}
