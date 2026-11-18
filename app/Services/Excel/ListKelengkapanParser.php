<?php

namespace App\Services\Excel;

use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ListKelengkapanParser
{
    /**
     * Parse the Excel file into an array structure.
     *
     * @return array<int, array{kode: string, nama: string, dokumen: array<int, string>}>
     */
    public function parse(string $filePath): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();

        $accounts = [];
        $colIndex = 2; // Column B

        while (true) {
            $cellVal = $sheet->getCell([$colIndex, 1])->getValue();
            if ($cellVal === null || trim((string) $cellVal) === '') {
                break;
            }

            $nama = trim((string) $cellVal);
            preg_match('/^(\d+)/', $nama, $matches);
            $kode = $matches[1] ?? 'GAJI';

            $docs = [];
            for ($r = 2; $r <= $highestRow; $r++) {
                $docVal = $sheet->getCell([$colIndex, $r])->getValue();
                if ($docVal !== null && trim((string) $docVal) !== '') {
                    $docs[] = trim((string) $docVal);
                }
            }

            $accounts[] = [
                'kode' => $kode,
                'nama' => $nama,
                'dokumen' => $docs,
            ];

            $colIndex++;
        }

        return $accounts;
    }

    /**
     * Import parsed accounts and their mandatory documents into database.
     *
     * @return array{accounts: int, documents: int}
     */
    public function import(string $filePath): array
    {
        $parsed = $this->parse($filePath);
        $totalDocs = 0;

        DB::transaction(function () use ($parsed, &$totalDocs) {
            foreach ($parsed as $item) {
                /** @var AkunBelanja $akun */
                $akun = AkunBelanja::updateOrCreate(
                    ['nama' => $item['nama']],
                    [
                        'kode' => $item['kode'],
                        'is_active' => true,
                    ]
                );

                $docNames = [];
                foreach ($item['dokumen'] as $order => $docName) {
                    $urutan = $order + 1;
                    $docNames[] = $docName;

                    DokumenWajib::withTrashed()->updateOrCreate(
                        [
                            'akun_belanja_id' => $akun->id,
                            'nama' => $docName,
                        ],
                        [
                            'urutan' => $urutan,
                            'is_active' => true,
                            'deleted_at' => null,
                        ]
                    );

                    $totalDocs++;
                }

                // Deactivate / soft-delete any existing documents no longer present in file
                DokumenWajib::where('akun_belanja_id', $akun->id)
                    ->whereNotIn('nama', $docNames)
                    ->delete();
            }
        });

        return [
            'accounts' => count($parsed),
            'documents' => $totalDocs,
        ];
    }
}
