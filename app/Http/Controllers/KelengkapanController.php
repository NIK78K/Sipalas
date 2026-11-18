<?php

namespace App\Http\Controllers;

use App\Models\AkunBelanja;
use App\Models\Satker;
use App\Services\RekapKelengkapanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class KelengkapanController extends Controller
{
    public function index(Request $request, RekapKelengkapanService $rekapService): Response
    {
        $satkerId = $request->filled('satker_id') ? (int) $request->input('satker_id') : null;
        $tahun = (int) ($request->input('tahun') ?: 2026);
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;

        $accounts = $rekapService->getAkunSummary($satkerId, $tahun, $bulan);
        $satkers = Satker::orderBy('kode')->get(['id', 'kode', 'nama']);

        return Inertia::render('Kelengkapan/Index', [
            'accounts' => $accounts,
            'satkers' => $satkers,
            'filters' => [
                'satker_id' => $satkerId ? (string) $satkerId : '',
                'tahun' => (string) $tahun,
                'bulan' => $bulan ? (string) $bulan : '',
            ],
        ]);
    }

    public function show(Request $request, AkunBelanja $akun, RekapKelengkapanService $rekapService): Response
    {
        $satkerId = $request->filled('satker_id') ? (int) $request->input('satker_id') : null;
        $tahun = (int) ($request->input('tahun') ?: 2026);
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;

        $detail = $rekapService->getAkunDetail($akun, $satkerId, $tahun, $bulan);
        $satkers = Satker::orderBy('kode')->get(['id', 'kode', 'nama']);

        return Inertia::render('Kelengkapan/Show', [
            'account' => $detail['account'],
            'total_spm' => $detail['total_spm'],
            'documents' => $detail['documents'],
            'satkers' => $satkers,
            'filters' => [
                'satker_id' => $satkerId ? (string) $satkerId : '',
                'tahun' => (string) $tahun,
                'bulan' => $bulan ? (string) $bulan : '',
            ],
        ]);
    }
}
