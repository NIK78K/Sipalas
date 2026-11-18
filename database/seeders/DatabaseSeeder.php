<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['username' => 'operator'],
            [
                'name' => 'Operator Utama',
                'email' => 'operator@bps.go.id',
                'password' => 'operator123',
                'role' => 'operator',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['username' => 'admin'],
            [
                'name' => 'Admin Pengawasan',
                'email' => 'admin@bps.go.id',
                'password' => 'admin123',
                'role' => 'admin',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        User::updateOrCreate(
            ['username' => 'user'],
            [
                'name' => 'Pegawai Verifikator',
                'email' => 'user@bps.go.id',
                'password' => 'user123',
                'role' => 'user',
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $this->call(SatkerSeeder::class);
        $this->call(ListKelengkapanSeeder::class);
        $this->call(CatatanPemeriksaSeeder::class);
    }
}
