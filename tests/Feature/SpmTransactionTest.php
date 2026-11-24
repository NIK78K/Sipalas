<?php

namespace Tests\Feature;

use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\SpmCatatan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpmTransactionTest extends TestCase
{
    use RefreshDatabase;

    private Satker $satker;

    private AkunBelanja $akun;

    private DokumenWajib $doc1;

    private DokumenWajib $doc2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->satker = Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);
        $this->akun = AkunBelanja::create(['kode' => '521111', 'nama' => '521111 Belanja Kantor']);

        $this->doc1 = DokumenWajib::create([
            'akun_belanja_id' => $this->akun->id,
            'nama' => 'SP2D',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $this->doc2 = DokumenWajib::create([
            'akun_belanja_id' => $this->akun->id,
            'nama' => 'SPM',
            'urutan' => 2,
            'is_active' => true,
        ]);
    }

    public function test_regular_user_can_view_spm_but_cannot_modify(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $spm = Spm::create([
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 5000000,
        ]);

        // Can view
        $this->actingAs($user)->get(route('spm.index'))->assertOk();
        $this->actingAs($user)->get(route('spm.show', $spm))->assertOk();

        // Cannot create
        $this->actingAs($user)->get(route('spm.create'))->assertForbidden();
        $this->actingAs($user)->post(route('spm.store'), [])->assertForbidden();

        // Cannot update or delete
        $this->actingAs($user)->put(route('spm.update', $spm), [])->assertForbidden();
        $this->actingAs($user)->delete(route('spm.destroy', $spm))->assertForbidden();

        // Cannot examine checklist
        $this->actingAs($user)->post(route('spm.checklist.update', $spm), [])->assertForbidden();
    }

    public function test_admin_can_create_spm_and_auto_sync_checklists(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('spm.store'), [
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 7500000,
            'tanggal_pemeriksaan' => '2026-01-15',
        ]);

        $this->assertDatabaseHas('spms', [
            'satker_id' => $this->satker->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
        ]);

        $spm = Spm::where('nomor', '00001A')->first();
        $response->assertRedirect(route('spm.show', $spm));

        // Check auto-initialized checklist items
        $this->assertCount(2, $spm->checklists);
        $this->assertEquals(0, $spm->checked_count);
        $this->assertEquals('Kurang 2', $spm->status_label);
        $this->assertFalse($spm->is_lengkap);
    }

    public function test_spm_number_must_be_unique_per_satker_year_month(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        Spm::create([
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);

        // Attempt duplicate in same satker, year, month
        $response = $this->actingAs($admin)->post(route('spm.store'), [
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 2000000,
        ]);

        $response->assertSessionHasErrors('nomor');

        // Different month should be allowed
        $responseDiffMonth = $this->actingAs($admin)->post(route('spm.store'), [
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 2,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 2000000,
        ]);
        $responseDiffMonth->assertSessionHasNoErrors();
    }

    public function test_atomic_checklist_update_and_optimistic_locking(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $spm = Spm::create([
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
            'version' => 1,
        ]);
        $spm->syncChecklists();

        // 1. Check one document
        $response = $this->actingAs($admin)->post(route('spm.checklist.update', $spm), [
            'version' => 1,
            'checked_ids' => [$this->doc1->id],
            'catatan_pemeriksa' => 'Kurang SPM fisik',
        ]);

        $response->assertRedirect();
        $spm->refresh();
        $this->assertEquals(2, $spm->version);
        $this->assertEquals(1, $spm->checked_count);
        $this->assertEquals('Kurang 1', $spm->status_label);
        $this->assertFalse($spm->is_lengkap);

        // 2. Conflict: submit with stale version (version 1 while current is 2)
        $conflictResponse = $this->actingAs($admin)->post(route('spm.checklist.update', $spm), [
            'version' => 1,
            'checked_ids' => [$this->doc1->id, $this->doc2->id],
        ]);
        $conflictResponse->assertSessionHasErrors('version');
        $this->assertEquals(2, $spm->fresh()->version);

        // 3. Complete checklist with correct version 2
        $completeResponse = $this->actingAs($admin)->post(route('spm.checklist.update', $spm), [
            'version' => 2,
            'checked_ids' => [$this->doc1->id, $this->doc2->id],
            'catatan_pemeriksa' => 'Lengkap dan sah',
        ]);
        $completeResponse->assertSessionHasNoErrors();

        $spm->refresh();
        $this->assertEquals(3, $spm->version);
        $this->assertEquals(2, $spm->checked_count);
        $this->assertTrue($spm->is_lengkap);
        $this->assertEquals('Lengkap', $spm->status_label);
        $this->assertEquals('Lengkap dan sah', $spm->catatan_pemeriksa);
    }

    public function test_additional_notes_creation_and_15_minute_deletion_rule(): void
    {
        $user1 = User::factory()->create(['role' => 'user']);
        $user2 = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $spm = Spm::create([
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);

        // User1 creates note
        $this->actingAs($user1)->post(route('spm.catatan.store', $spm), [
            'isi' => 'Kuitansi belum distempel basah.',
        ])->assertRedirect();

        $note = SpmCatatan::where('spm_id', $spm->id)->first();
        $this->assertNotNull($note);
        $this->assertEquals($user1->id, $note->user_id);

        // User2 cannot delete user1's note
        $this->actingAs($user2)->delete(route('spm.catatan.destroy', $note))->assertForbidden();

        // User1 can delete within 15 minutes
        $this->assertTrue($note->canDeleteBy($user1));

        // Simulate note created 20 minutes ago
        $note->created_at = now()->subMinutes(20);
        $note->save();

        $this->assertFalse($note->fresh()->canDeleteBy($user1));
        $this->actingAs($user1)->delete(route('spm.catatan.destroy', $note))->assertForbidden();

        // Admin can delete regardless of time
        $this->actingAs($admin)->delete(route('spm.catatan.destroy', $note))->assertRedirect();
        $this->assertDatabaseMissing('spm_catatans', ['id' => $note->id]);
    }

    public function test_users_can_reply_to_additional_notes(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $admin = User::factory()->create(['role' => 'admin']);

        $spm = Spm::create([
            'satker_id' => $this->satker->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00002A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);

        $this->actingAs($user)->post(route('spm.catatan.store', $spm), [
            'isi' => 'Dokumen A dan B sudah diperiksa.',
        ])->assertRedirect();

        $note = SpmCatatan::where('spm_id', $spm->id)->firstOrFail();

        $this->actingAs($admin)->post(route('spm.catatan.store', $spm), [
            'isi' => 'Diterima, terima kasih.',
            'parent_id' => $note->id,
        ])->assertRedirect();

        $reply = SpmCatatan::where('parent_id', $note->id)->firstOrFail();
        $this->assertEquals($admin->id, $reply->user_id);
        $this->assertEquals($note->id, $reply->parent_id);
        $this->assertEquals(1, $spm->catatans()->count());
        $this->assertEquals(1, $note->replies()->count());
    }

}
