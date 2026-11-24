<?php

namespace Tests\Feature;

use App\Jobs\ProcessCatatanPemeriksaImport;
use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\ImportLog;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\User;
use App\Services\Excel\CatatanPemeriksaParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class CatatanPemeriksaImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Setup base satkers
        Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);
        Satker::create(['kode' => '1407', 'nama' => 'Kab. Rokan Hulu']);

        // Setup base account
        $akun = AkunBelanja::create([
            'kode' => 'GAJI',
            'nama' => 'Gaji Induk PNS/Kekurangan Gaji PNS/Gaji Induk PPPK/Kekurangan Gaji PPPK',
        ]);

        DokumenWajib::create([
            'akun_belanja_id' => $akun->id,
            'nama' => 'SP2D',
            'urutan' => 1,
            'is_active' => true,
        ]);
        DokumenWajib::create([
            'akun_belanja_id' => $akun->id,
            'nama' => 'SPM',
            'urutan' => 2,
            'is_active' => true,
        ]);
    }

    public function test_user_cannot_access_import(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('impor.index'))->assertForbidden();
        $this->actingAs($user)->post(route('impor.pemeriksa'), [])->assertForbidden();
    }

    public function test_admin_can_access_and_trigger_import(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $filePath = base_path('Catatan Pemeriksa New.xlsx');

        if (! File::exists($filePath)) {
            $this->markTestSkipped('Catatan Pemeriksa New.xlsx tidak ditemukan.');
        }

        $this->actingAs($admin)->get(route('impor.index'))->assertOk();

        $file = new UploadedFile(
            $filePath,
            'Catatan_Pemeriksa_New.xlsx',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            null,
            true
        );

        $response = $this->actingAs($admin)->post(route('impor.pemeriksa'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('import_logs', [
            'nama_file' => 'Catatan_Pemeriksa_New.xlsx',
        ]);

        $log = ImportLog::first();
        $this->assertNotNull($log);

        // Status endpoint check
        $statusResponse = $this->actingAs($admin)->get(route('impor.status', $log));
        $statusResponse->assertOk();
        $statusResponse->assertJsonStructure(['id', 'status', 'berhasil', 'dilewati']);
    }

    public function test_catatan_pemeriksa_parser_upsert_and_cleaning(): void
    {
        $filePath = base_path('Catatan Pemeriksa New.xlsx');

        if (! File::exists($filePath)) {
            $this->markTestSkipped('Catatan Pemeriksa New.xlsx tidak ditemukan.');
        }

        $log = ImportLog::create([
            'nama_file' => 'Test_Import.xlsx',
            'status' => 'pending',
            'tipe' => 'catatan_pemeriksa',
        ]);

        $parser = new CatatanPemeriksaParser();
        $result = $parser->import($filePath, $log);

        $this->assertGreaterThan(0, $result['berhasil']);
        $this->assertEquals('completed', $log->fresh()->status);

        // Verify SPM 00001A for Satker 1401 was upserted properly
        $satker = Satker::where('kode', '1401')->first();
        $this->assertDatabaseHas('spms', [
            'satker_id' => $satker->id,
            'bulan' => 1,
            'nomor' => '00001A',
        ]);

        $spm = Spm::where('satker_id', $satker->id)->where('nomor', '00001A')->first();
        $this->assertNotNull($spm);
        $this->assertEquals(132090617, $spm->nilai_transaksi);
        $this->assertNotEmpty($spm->checklists);

    }
}
