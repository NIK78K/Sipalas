<?php

namespace Database\Seeders;

use App\Models\Satker;
use Illuminate\Database\Seeder;

class SatkerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $satkers = [
            ['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi'],
            ['kode' => '1402', 'nama' => 'Kab. Indragiri Hulu'],
            ['kode' => '1403', 'nama' => 'Kab. Indragiri Hilir'],
            ['kode' => '1404', 'nama' => 'Kab. Pelalawan'],
            ['kode' => '1405', 'nama' => 'Kab. Siak'],
            ['kode' => '1406', 'nama' => 'Kab. Kampar'],
            ['kode' => '1407', 'nama' => 'Kab. Rokan Hulu'],
            ['kode' => '1408', 'nama' => 'Kab. Bengkalis'],
            ['kode' => '1409', 'nama' => 'Kab. Rokan Hilir'],
            ['kode' => '1410', 'nama' => 'Kab. Kepulauan Meranti'],
            ['kode' => '1471', 'nama' => 'Kota Pekanbaru'],
            ['kode' => '1473', 'nama' => 'Kota Dumai'],
        ];

        foreach ($satkers as $satker) {
            Satker::updateOrCreate(
                ['kode' => $satker['kode']],
                ['nama' => $satker['nama']]
            );
        }
    }
}
