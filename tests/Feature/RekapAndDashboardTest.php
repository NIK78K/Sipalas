<?php

namespace Tests\Feature;

use App\Models\AkunBelanja;
use App\Models\DokumenWajib;
use App\Models\Satker;
use App\Models\Spm;
use App\Models\User;
use App\Services\RekapKelengkapanService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RekapAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    private Satker $satker1;

    private Satker $satker2;

    private AkunBelanja $akun;

    private DokumenWajib $doc1;

    private DokumenWajib $doc2;

    protected function setUp(): void
    {
        parent::setUp();

        $this->satker1 = Satker::create(['kode' => '1401', 'nama' => 'Kab. Kuantan Singingi']);
        $this->satker2 = Satker::create(['kode' => '1402', 'nama' => 'Kab. Indragiri Hulu']);

        $this->akun = AkunBelanja::create(['kode' => '521211', 'nama' => '521211 Konsumsi Rapat']);

        $this->doc1 = DokumenWajib::create([
            'akun_belanja_id' => $this->akun->id,
            'nama' => 'SP2D',
            'urutan' => 1,
            'is_active' => true,
        ]);

        $this->doc2 = DokumenWajib::create([
            'akun_belanja_id' => $this->akun->id,
            'nama' => 'Undangan',
            'urutan' => 2,
            'is_active' => true,
        ]);
    }

    public function test_dashboard_renders_metrics_and_provincial_totals(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // SPM 1 (Satker 1): Lengkap (both docs checked)
        $spm1 = Spm::create([
            'satker_id' => $this->satker1->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 3000000,
        ]);
        $spm1->syncChecklists();
        $spm1->checklists()->update(['is_checked' => true]);

        // SPM 2 (Satker 2): Kurang (only doc1 checked)
        $spm2 = Spm::create([
            'satker_id' => $this->satker2->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00002A',
            'jenis' => 'LS',
            'nilai_transaksi' => 2000000,
        ]);
        $spm2->syncChecklists();
        $spm2->checklists()->where('dokumen_wajib_id', $this->doc1->id)->update(['is_checked' => true]);

        $response = $this->actingAs($user)->get(route('dashboard'));
        $response->assertOk();

        // Test Service directly
        $service = new RekapKelengkapanService();
        $summary = $service->getSatkerSummary(2026, 1);

        $this->assertEquals(2, $summary['totals']['total_spm']);
        $this->assertEquals(1, $summary['totals']['total_lengkap']);
        $this->assertEquals(1, $summary['totals']['total_kurang']);
        $this->assertEquals(5000000, $summary['totals']['total_nilai']);
        $this->assertEquals(50.0, $summary['totals']['overall_percent']);

        $s1 = $summary['satkers']->firstWhere('satker_id', $this->satker1->id);
        $this->assertEquals(1, $s1['total_spm']);
        $this->assertEquals(1, $s1['spm_lengkap']);
        $this->assertEquals(100.0, $s1['persen_lengkap']);

        $s2 = $summary['satkers']->firstWhere('satker_id', $this->satker2->id);
        $this->assertEquals(1, $s2['total_spm']);
        $this->assertEquals(0, $s2['spm_lengkap']);
        $this->assertEquals(0.0, $s2['persen_lengkap']);
    }

    public function test_list_kelengkapan_summary_and_detail_per_document(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        // SPM 1: doc1 checked, doc2 checked
        $spm1 = Spm::create([
            'satker_id' => $this->satker1->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);
        $spm1->syncChecklists();
        $spm1->checklists()->update(['is_checked' => true]);

        // SPM 2: doc1 checked, doc2 UNCHECKED
        $spm2 = Spm::create([
            'satker_id' => $this->satker1->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00002A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1500000,
        ]);
        $spm2->syncChecklists();
        $spm2->checklists()->where('dokumen_wajib_id', $this->doc1->id)->update(['is_checked' => true]);

        // 1. Index page
        $this->actingAs($user)->get(route('kelengkapan.index'))->assertOk();

        // 2. Detail page
        $this->actingAs($user)->get(route('kelengkapan.show', $this->akun))->assertOk();

        // 3. Test Service detail
        $service = new RekapKelengkapanService();
        $detail = $service->getAkunDetail($this->akun, null, 2026, 1);

        $this->assertEquals(2, $detail['total_spm']);
        $this->assertCount(2, $detail['documents']);

        // doc1 (SP2D): fulfilled by both SPMs (2/2 = 100%)
        $doc1Detail = $detail['documents']->firstWhere('id', $this->doc1->id);
        $this->assertEquals(2, $doc1Detail['fulfilled_count']);
        $this->assertEquals(100.0, $doc1Detail['percent']);
        $this->assertEmpty($doc1Detail['missing_spms']);

        // doc2 (Undangan): fulfilled by SPM1 only (1/2 = 50%)
        $doc2Detail = $detail['documents']->firstWhere('id', $this->doc2->id);
        $this->assertEquals(1, $doc2Detail['fulfilled_count']);
        $this->assertEquals(50.0, $doc2Detail['percent']);
        $this->assertCount(1, $doc2Detail['missing_spms']);
        $this->assertEquals('00002A', $doc2Detail['missing_spms'][0]['nomor']);
    }

    public function test_excel_export_endpoints(): void
    {
        $user = User::factory()->create(['role' => 'user']);

        Spm::create([
            'satker_id' => $this->satker1->id,
            'akun_belanja_id' => $this->akun->id,
            'tahun' => 2026,
            'bulan' => 1,
            'nomor' => '00001A',
            'jenis' => 'LS',
            'nilai_transaksi' => 1000000,
        ]);

        // Export SPM list
        $responseSpm = $this->actingAs($user)->get(route('ekspor.spm'));
        $responseSpm->assertOk();
        $responseSpm->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

        // Export Kelengkapan
        $responseRekap = $this->actingAs($user)->get(route('ekspor.kelengkapan'));
        $responseRekap->assertOk();
        $responseRekap->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
