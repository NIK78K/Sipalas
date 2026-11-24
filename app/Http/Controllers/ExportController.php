<?php

namespace App\Http\Controllers;

use App\Models\Spm;
use App\Services\RekapKelengkapanService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportController extends Controller
{
    public function exportSpm(Request $request): StreamedResponse
    {
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

        $spms = $query->orderBy('satker_id')->orderBy('bulan')->orderBy('nomor')->get();

        if ($request->input('status') === 'lengkap') {
            $spms = $spms->filter(fn ($s) => $s->is_lengkap)->values();
        } elseif ($request->input('status') === 'kurang') {
            $spms = $spms->filter(fn ($s) => ! $s->is_lengkap)->values();
        }

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Daftar SPM');

        // Headers
        $headers = [
            'No', 'Satker', 'Tahun', 'Bulan', 'No SPM', 'Jenis',
            'Akun Belanja', 'Nilai Transaksi (Rp)', 'Status', 'Progres',
            'Uraian', 'Link Bukti Dukung',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        // Rows
        $row = 2;
        foreach ($spms as $idx => $spm) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValue("B{$row}", "[{$spm->satker?->kode}] {$spm->satker?->nama}");
            $sheet->setCellValue("C{$row}", $spm->tahun);
            $sheet->setCellValue("D{$row}", $spm->bulan);
            $sheet->setCellValueExplicit("E{$row}", $spm->nomor, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("F{$row}", $spm->jenis);
            $sheet->setCellValue("G{$row}", $spm->akunBelanja?->nama);
            $sheet->setCellValue("H{$row}", $spm->nilai_transaksi);
            $sheet->setCellValue("I{$row}", $spm->status_label);
            $sheet->setCellValue("J{$row}", "{$spm->checked_count}/{$spm->total_docs_count}");
            $sheet->setCellValue("K{$row}", $spm->catatan_pemeriksa);
            $sheet->setCellValue("L{$row}", $spm->link_bukti);
            $row++;
        }

        $filename = 'Daftar_SPM_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }

    public function exportKelengkapan(Request $request, RekapKelengkapanService $rekapService): StreamedResponse
    {
        $satkerId = $request->filled('satker_id') ? (int) $request->input('satker_id') : null;
        $tahun = (int) ($request->input('tahun') ?: 2026);
        $bulan = $request->filled('bulan') ? (int) $request->input('bulan') : null;

        $accounts = $rekapService->getAkunSummary($satkerId, $tahun, $bulan);

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekap Kelengkapan');

        $headers = [
            'No', 'Kode Akun', 'Nama Akun Belanja', 'Jml Dokumen Wajib',
            'Total SPM', 'SPM Lengkap', 'SPM Kurang', 'Persen Lengkap (%)',
            'Total Nilai Transaksi (Rp)',
        ];

        $col = 'A';
        foreach ($headers as $h) {
            $sheet->setCellValue("{$col}1", $h);
            $sheet->getColumnDimension($col)->setAutoSize(true);
            $col++;
        }

        $row = 2;
        foreach ($accounts as $idx => $acc) {
            $sheet->setCellValue("A{$row}", $idx + 1);
            $sheet->setCellValueExplicit("B{$row}", $acc['kode'], \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
            $sheet->setCellValue("C{$row}", $acc['nama']);
            $sheet->setCellValue("D{$row}", $acc['total_docs']);
            $sheet->setCellValue("E{$row}", $acc['total_spm']);
            $sheet->setCellValue("F{$row}", $acc['spm_lengkap']);
            $sheet->setCellValue("G{$row}", $acc['spm_kurang']);
            $sheet->setCellValue("H{$row}", "{$acc['persen_lengkap']}%");
            $sheet->setCellValue("I{$row}", $acc['nilai_transaksi']);
            $row++;
        }

        $filename = 'Rekap_List_Kelengkapan_' . date('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }
}
