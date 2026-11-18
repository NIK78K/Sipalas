<?php

namespace Database\Seeders;

use App\Models\ImportLog;
use App\Models\User;
use App\Services\Excel\CatatanPemeriksaParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class CatatanPemeriksaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('Catatan Pemeriksa New.xlsx');

        if (File::exists($filePath)) {
            $operator = User::where('role', 'operator')->first();

            $log = ImportLog::create([
                'user_id' => $operator?->id,
                'tipe' => 'catatan_pemeriksa',
                'nama_file' => 'Catatan Pemeriksa New.xlsx',
                'status' => 'processing',
                'berhasil' => 0,
                'dilewati' => 0,
            ]);

            $parser = new CatatanPemeriksaParser();
            $result = $parser->import($filePath, $log);

            $this->command->info("Catatan Pemeriksa berhasil diimpor: {$result['berhasil']} SPM, {$result['dilewati']} baris kosong dilewati.");
        }
    }
}
