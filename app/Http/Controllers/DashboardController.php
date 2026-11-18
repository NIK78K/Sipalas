<?php

namespace App\Http\Controllers;

use App\Services\RekapKelengkapanService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request, RekapKelengkapanService $rekapService): Response
    {
        $tahun = (int) ($request->input('tahun') ?: 2026);
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;

        $summary = $rekapService->getSatkerSummary($tahun, $bulan);

        return Inertia::render('Dashboard', [
            'satkers' => $summary['satkers'],
            'totals' => $summary['totals'],
            'filters' => [
                'tahun' => (string) $tahun,
                'bulan' => $bulan ? (string) $bulan : '',
            ],
        ]);
    }
}
