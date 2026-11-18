<?php

namespace Database\Seeders;

use App\Services\Excel\ListKelengkapanParser;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;

class ListKelengkapanSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $filePath = base_path('List Kelengkapan.xlsx');

        if (File::exists($filePath)) {
            $parser = new ListKelengkapanParser();
            $result = $parser->import($filePath);
            $this->command->info("List Kelengkapan berhasil diimpor: {$result['accounts']} akun, {$result['documents']} dokumen.");
        }
    }
}
