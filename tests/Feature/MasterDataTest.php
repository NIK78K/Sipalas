<?php

namespace Tests\Feature;

use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\Satker;
use App\Models\User;
use App\Services\Excel\ListKelengkapanParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class MasterDataTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_access_master_data(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('master.satker.index'))->assertForbidden();
        $this->actingAs($user)->get(route('master.akun.index'))->assertForbidden();
    }

    public function test_admin_and_operator_can_access_master_data(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($admin)->get(route('master.satker.index'))->assertOk();
        $this->actingAs($admin)->get(route('master.akun.index'))->assertOk();

        $this->actingAs($operator)->get(route('master.satker.index'))->assertOk();
        $this->actingAs($operator)->get(route('master.akun.index'))->assertOk();
    }

    public function test_admin_can_crud_satker(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create
        $response = $this->actingAs($admin)->post(route('master.satker.store'), [
            'kode' => '1499',
            'nama' => 'Satker Uji Coba',
        ]);
        $response->assertRedirect();
        $this->assertDatabaseHas('satkers', ['kode' => '1499', 'nama' => 'Satker Uji Coba']);

        /** @var Satker $satker */
        $satker = Satker::where('kode', '1499')->first();

        // Update
        $this->actingAs($admin)->put(route('master.satker.update', $satker), [
            'kode' => '1499',
            'nama' => 'Satker Diperbarui',
        ])->assertRedirect();
        $this->assertDatabaseHas('satkers', ['kode' => '1499', 'nama' => 'Satker Diperbarui']);

        // Delete
        $this->actingAs($admin)->delete(route('master.satker.destroy', $satker))->assertRedirect();
        $this->assertDatabaseMissing('satkers', ['kode' => '1499']);
    }

    public function test_satker_name_must_be_unique(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);

        $this->actingAs($admin)
            ->from(route('master.satker.index'))
            ->post(route('master.satker.store'), [
                'kode' => '1402',
                'nama' => 'Kab. Kuantan Singingi',
            ])
            ->assertRedirect(route('master.satker.index'))
            ->assertSessionHasErrors([
                'nama' => 'Nama satker sudah terdaftar.',
            ]);
    }

    public function test_admin_can_crud_akun_belanja(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create
        $this->actingAs($admin)->post(route('master.akun.store'), [
            'kode' => '521999',
            'nama' => '521999 Belanja Uji Coba',
        ])->assertRedirect();

        $this->assertDatabaseHas('akun_belanjas', ['kode' => '521999']);

        /** @var AkunBelanja $akun */
        $akun = AkunBelanja::where('kode', '521999')->first();

        // Show detail
        $this->actingAs($admin)->get(route('master.akun.show', $akun))->assertOk();

        // Update
        $this->actingAs($admin)->put(route('master.akun.update', $akun), [
            'kode' => '521999',
            'nama' => '521999 Belanja Revisi',
            'is_active' => true,
        ])->assertRedirect();
        $this->assertDatabaseHas('akun_belanjas', ['nama' => '521999 Belanja Revisi']);

        // Toggle
        $this->actingAs($admin)->patch(route('master.akun.toggle', $akun))->assertRedirect();
        $this->assertFalse($akun->fresh()->is_active);

        // Delete
        $this->actingAs($admin)->delete(route('master.akun.destroy', $akun))->assertRedirect();
        $this->assertDatabaseMissing('akun_belanjas', ['id' => $akun->id]);
    }

    public function test_admin_can_manage_dokumen_wajib_and_soft_delete(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $akun = AkunBelanja::create([
            'kode' => '521111',
            'nama' => '521111 Belanja Kantor Test',
            'is_active' => true,
        ]);

        // Add 2 documents
        $this->actingAs($admin)->post(route('master.dokumen.store', $akun), ['nama' => 'SP2D'])->assertRedirect();
        $this->actingAs($admin)->post(route('master.dokumen.store', $akun), ['nama' => 'SPM'])->assertRedirect();

        $this->assertCount(2, $akun->dokumenWajibs);

        $doc1 = $akun->dokumenWajibs()->where('nama', 'SP2D')->first();
        $doc2 = $akun->dokumenWajibs()->where('nama', 'SPM')->first();

        $this->assertEquals(1, $doc1->urutan);
        $this->assertEquals(2, $doc2->urutan);

        // Reorder (swap order)
        $this->actingAs($admin)->post(route('master.dokumen.reorder'), [
            'ids' => [$doc2->id, $doc1->id],
        ])->assertRedirect();

        $this->assertEquals(1, $doc2->fresh()->urutan);
        $this->assertEquals(2, $doc1->fresh()->urutan);

        // Soft delete (FR-07)
        $this->actingAs($admin)->delete(route('master.dokumen.destroy', $doc1))->assertRedirect();
        $this->assertSoftDeleted('dokumen_wajibs', ['id' => $doc1->id]);
        $this->assertCount(1, $akun->dokumenWajibs()->get());
        $this->assertCount(2, $akun->dokumenWajibs()->withTrashed()->get());
    }

    public function test_list_kelengkapan_excel_parser_and_import_endpoint(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $excelPath = base_path('List Kelengkapan.xlsx');

        if (! File::exists($excelPath)) {
            $this->markTestSkipped('List Kelengkapan.xlsx tidak ditemukan.');
        }

        // Test parser directly
        $parser = new ListKelengkapanParser();
        $parsed = $parser->parse($excelPath);

        $this->assertCount(35, $parsed);
        $this->assertNotEmpty($parsed[0]['dokumen']);

        // Test upload via endpoint
        $file = new UploadedFile($excelPath, 'List_Kelengkapan.xlsx', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', null, true);

        $response = $this->actingAs($admin)->post(route('master.akun.import'), [
            'file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('akun_belanjas', ['nama' => '521111 Belanja Keperluan Perkantoran']);
        $this->assertGreaterThanOrEqual(35, AkunBelanja::count());
        $this->assertGreaterThanOrEqual(400, DokumenWajib::count());
    }
}
