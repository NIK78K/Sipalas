<?php

namespace App\Services\Excel;

use App\Models\AkunBelanja;
use App\Models\ImportLog;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\SpmChecklist;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;

class CatatanPemeriksaParser
{
    /**
     * Parse and import Catatan_Pemeriksa_New.xlsx into database.
     *
     * @param  \Closure(int $berhasil, int $dilewati, string $message): void|null  $progressCallback
     * @return array{berhasil: int, dilewati: int, warnings: array<int, string>, unmatched_accounts: array<int, string>}
     */
    public function import(string $filePath, ?ImportLog $importLog = null, ?\Closure $progressCallback = null): array
    {
        ini_set('memory_limit', '1024M');
        set_time_limit(600);

        $berhasil = 0;
        $dilewati = 0;
        $warnings = [];
        $unmatchedAccounts = [];

        // Pre-fetch satkers and accounts
        $satkers = Satker::all()->keyBy('kode');
        $accounts = AkunBelanja::with(['dokumenWajibs' => fn ($q) => $q->orderBy('urutan')])->get();
        $accountByName = [];
        foreach ($accounts as $acc) {
            $accountByName[mb_strtolower(trim($acc->nama))] = $acc;
        }

        $defaultAccount = $accounts->first();

        // 1. Get sheet names using reader without loading all data
        $reader = IOFactory::createReaderForFile($filePath);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $sheetNames = $reader->listWorksheetNames($filePath);

        // 2. Process each satker sheet individually (memory-efficient)
        foreach ($sheetNames as $sheetName) {
            if ($sheetName === 'kosong') {
                continue;
            }

            $satkerKode = trim($sheetName);
            $satker = $satkers->get($satkerKode);

            if (! $satker) {
                continue;
            }

            // Load only this worksheet
            $reader->setLoadSheetsOnly([$sheetName]);
            $spreadsheet = $reader->load($filePath);
            $sheet = $spreadsheet->getActiveSheet();
            $maxRow = min($sheet->getHighestRow(), 6500);

            DB::transaction(function () use (
                $sheet,
                $satker,
                $satkerKode,
                $maxRow,
                $accountByName,
                $defaultAccount,
                &$berhasil,
                &$dilewati,
                &$warnings,
                &$unmatchedAccounts,
                $progressCallback
            ) {
                // 12 months
                for ($m = 1; $m <= 12; $m++) {
                    $baseCol = 1 + (($m - 1) * 4);
                    $valCol = $baseCol + 2;

                    // Scan rows for "SPM No"
                    for ($r = 1; $r <= $maxRow; $r++) {
                        $labelVal = $sheet->getCell([$baseCol, $r])->getValue();

                        if (! $labelVal || ! str_contains(strval($labelVal), 'SPM No')) {
                            continue;
                        }

                        $nomorRaw = $sheet->getCell([$valCol, $r])->getValue();

                        if ($nomorRaw === null) {
                            $dilewati++;
                            continue;
                        }

                        $cleanNomor = trim(str_replace([':', ' '], '', strval($nomorRaw)));

                        if ($cleanNomor === '' || $cleanNomor === ':') {
                            $dilewati++;
                            continue;
                        }

                        // Warning if purely numeric
                        if (is_numeric($cleanNomor)) {
                            $warnings[] = "[{$satkerKode}] Bulan {$m}: No SPM berupa angka murni '{$cleanNomor}' pada baris {$r}.";
                        }

                        // Parse Tanggal (row r - 1)
                        $tglRaw = $sheet->getCell([$valCol, $r - 1])->getValue();
                        $tglParsed = null;

                        if ($tglRaw) {
                            if (is_numeric($tglRaw)) {
                                try {
                                    $tglParsed = Date::excelToDateTimeObject((float) $tglRaw)->format('Y-m-d');
                                } catch (\Throwable) {
                                    $tglParsed = null;
                                }
                            } else {
                                $tglStr = trim(str_replace([':', ' '], '', strval($tglRaw)));
                                try {
                                    if (preg_match('/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/', $tglStr, $tMatches)) {
                                        $day = (int) $tMatches[1];
                                        $month = (int) $tMatches[2];
                                        $year = (int) $tMatches[3];

                                        // Month mismatch detection (FR-10 / Section 10)
                                        if ($month !== $m && $day === $m) {
                                            $warnings[] = "[{$satkerKode}] No SPM {$cleanNomor}: Tanggal '{$tglStr}' tampak tertukar hari/bulan dengan bulan sheet {$m}.";
                                        }

                                        $tglParsed = sprintf('%04d-%02d-%02d', $year, $month, $day);
                                    }
                                } catch (\Throwable) {
                                    $tglParsed = null;
                                }
                            }
                        }

                        // Parse Jenis (row r + 1)
                        $jenisRaw = $sheet->getCell([$valCol, $r + 1])->getValue();
                        $cleanJenis = $jenisRaw ? trim(str_replace(':', '', strval($jenisRaw))) : 'LS';
                        if ($cleanJenis === '') {
                            $cleanJenis = 'LS';
                        }

                        // Parse Akun (row r + 2)
                        $akunRaw = $sheet->getCell([$valCol, $r + 2])->getValue();
                        $cleanAkunName = $akunRaw ? trim(preg_replace('/\s+/', ' ', str_replace(':', '', strval($akunRaw)))) : '';
                        $matchedAccount = $accountByName[mb_strtolower($cleanAkunName)] ?? null;

                        if (! $matchedAccount) {
                            $unmatchedAccounts[] = "[{$satkerKode}] No SPM {$cleanNomor}: Akun '{$cleanAkunName}' tidak cocok dengan master.";
                            $matchedAccount = $defaultAccount;
                        }

                        // Parse Nilai (row r + 3)
                        $nilaiRaw = $sheet->getCell([$valCol, $r + 3])->getValue();
                        $cleanNilai = 0;
                        if ($nilaiRaw !== null) {
                            if (is_numeric($nilaiRaw)) {
                                $cleanNilai = (int) round((float) $nilaiRaw);
                            } else {
                                $numStr = preg_replace('/[^0-9]/', '', strval($nilaiRaw));
                                $cleanNilai = $numStr !== '' ? (int) $numStr : 0;
                            }
                        }

                        // Parse Catatan Pemeriksa (scan row r + 45 to r + 52)
                        $catatanPemeriksa = null;
                        for ($cr = $r + 45; $cr <= min($r + 52, $maxRow); $cr++) {
                            $c1 = $sheet->getCell([$baseCol, $cr])->getValue();
                            $c2 = $sheet->getCell([$valCol, $cr])->getValue();

                            if ($c1 && str_contains(strval($c1), 'Catatan Pemeriksa')) {
                                $catatanPemeriksa = trim(preg_replace('/^Catatan Pemeriksa\s*:\s*/i', '', strval($c1)));
                                break;
                            } elseif ($c2 && str_contains(strval($c2), 'Catatan Pemeriksa')) {
                                $catatanPemeriksa = trim(preg_replace('/^Catatan Pemeriksa\s*:\s*/i', '', strval($c2)));
                                break;
                            }
                        }

                        // Read checklist rows (up to 38 rows: r + 7 to r + 44)
                        $checklistChecks = [];
                        for ($cIndex = 1; $cIndex <= 38; $cIndex++) {
                            $chkCellVal = $sheet->getCell([$valCol, $r + 6 + $cIndex])->getValue();
                            $isChecked = ($chkCellVal === true || $chkCellVal === 1 || strtolower(trim(strval($chkCellVal))) === 'true');
                            $checklistChecks[$cIndex] = $isChecked;
                        }

                        /** @var Spm $spm */
                        $spm = Spm::updateOrCreate(
                            [
                                'satker_id' => $satker->id,
                                'tahun' => 2026,
                                'bulan' => $m,
                                'nomor' => $cleanNomor,
                            ],
                            [
                                'tanggal_pemeriksaan' => $tglParsed,
                                'jenis' => $cleanJenis,
                                'akun_belanja_id' => $matchedAccount->id,
                                'nilai_transaksi' => max(0, $cleanNilai),
                                'catatan_pemeriksa' => $catatanPemeriksa,
                            ]
                        );

                        // Sync checklists based on account document order
                        if ($matchedAccount && $matchedAccount->dokumenWajibs) {
                            foreach ($matchedAccount->dokumenWajibs as $doc) {
                                $isChecked = $checklistChecks[$doc->urutan] ?? false;

                                SpmChecklist::updateOrCreate(
                                    [
                                        'spm_id' => $spm->id,
                                        'dokumen_wajib_id' => $doc->id,
                                    ],
                                    [
                                        'is_checked' => $isChecked,
                                        'checked_at' => $isChecked ? now() : null,
                                    ]
                                );
                            }
                        }

                        $berhasil++;

                        if ($progressCallback && $berhasil % 50 === 0) {
                            $progressCallback($berhasil, $dilewati, "Memproses {$satker->nama} ({$cleanNomor})...");
                        }
                    }
                }
            });

            // Clean memory between sheets
            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
            gc_collect_cycles();
        }

        $report = [
            'berhasil' => $berhasil,
            'dilewati' => $dilewati,
            'warnings' => array_slice($warnings, 0, 100),
            'unmatched_accounts' => array_values(array_unique($unmatchedAccounts)),
        ];

        if ($importLog) {
            $importLog->update([
                'status' => 'completed',
                'berhasil' => $berhasil,
                'dilewati' => $dilewati,
                'laporan' => $report,
            ]);
        }

        return $report;
    }
}
