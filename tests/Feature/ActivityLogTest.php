<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\User;
use Illuminate\Auth\Events\Login;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    public function test_regular_user_cannot_view_activity_logs(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        $this->actingAs($user)->get(route('log.index'))->assertForbidden();
    }

    public function test_admin_and_operator_can_view_activity_logs(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $operator = User::factory()->create(['role' => 'operator']);

        $this->actingAs($admin)->get(route('log.index'))->assertOk();
        $this->actingAs($operator)->get(route('log.index'))->assertOk();
    }

    public function test_login_records_activity_log(): void
    {
        $user = User::factory()->create([
            'email' => 'loginuser@bps.go.id',
            'username' => 'loginuser',
            'role' => 'admin',
        ]);

        Event::dispatch(new Login('web', $user, false));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $user->id,
            'aksi' => 'login',
        ]);
    }

    public function test_checklist_save_records_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $satker = Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);
        $akun = AkunBelanja::create(['kode' => '521111', 'nama' => '521111 Belanja Kantor']);
        $doc = DokumenWajib::create([
            'akun_belanja_id' => $akun->id,
            'nama' => 'SP2D',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $spm = Spm::create([
            'satker_id' => $satker->id,
            'akun_belanja_id' => $akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
            'version' => 1,
        ]);

        $this->actingAs($admin)->post(route('spm.checklist.update', $spm), [
            'version' => 1,
            'statuses' => [$doc->id => 'sesuai'],
            'catatan_pemeriksa' => 'Catatan checklist test',
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'aksi' => 'perubahan_checklist',
            'subjek_id' => $spm->id,
        ]);
    }

    public function test_spm_deletion_records_activity_log(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $satker = Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);
        $akun = AkunBelanja::create(['kode' => '521111', 'nama' => '521111 Belanja Kantor']);

        $spm = Spm::create([
            'satker_id' => $satker->id,
            'akun_belanja_id' => $akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);

        $this->actingAs($admin)->delete(route('spm.destroy', $spm));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'aksi' => 'penghapusan_spm',
            'subjek_id' => $spm->id,
        ]);
    }

    public function test_user_role_change_records_activity_log(): void
    {
        $operator = User::factory()->create(['role' => 'operator']);
        $target = User::factory()->create(['role' => 'user']);

        $this->actingAs($operator)->put(route('users.update', $target), [
            'name' => $target->name,
            'username' => $target->username,
            'email' => $target->email,
            'role' => 'admin',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $operator->id,
            'aksi' => 'perubahan_peran',
            'subjek_id' => $target->id,
        ]);
    }
}
