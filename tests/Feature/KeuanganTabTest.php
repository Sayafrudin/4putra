<?php

namespace Tests\Feature;

use App\Models\FinancialTab;
use App\Models\Keuangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Multi-Tab Keuangan Proyek: tab tersimpan di DB, transaksi terikat tab
 * (NULL = Kas Umum), budget + biaya tambahan, dan pindah transaksi ke
 * Kas Umum saat tab dihapus.
 */
class KeuanganTabTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-keutab-test@4putra.test'],
            ['name' => 'Admin Keu Tab', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        DB::table('keuangans')->where('keterangan', 'like', 'uji-keu%')->delete();
        FinancialTab::where('nama', 'like', 'uji-tab%')->delete();
        User::where('email', 'admin-keutab-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_transaksi_ikuti_tab_aktif_dan_ringkasan_terpisah(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-pembangunan']);

        $this->actingAs($this->admin)->postJson('/admin/keuangan', [
            'tipe' => 'masuk', 'kategori' => 'Pembangunan', 'jumlah' => 100000,
            'tanggal' => '2026-11-01', 'financial_tab_id' => $tab->id, 'keterangan' => 'uji-keu-tab-masuk',
        ])->assertOk();

        $this->actingAs($this->admin)->postJson('/admin/keuangan', [
            'tipe' => 'masuk', 'kategori' => 'Penjualan', 'jumlah' => 500000,
            'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-kas-umum',
        ])->assertOk();

        $this->assertDatabaseHas('keuangans', ['keterangan' => 'uji-keu-tab-masuk', 'financial_tab_id' => $tab->id]);
        $this->assertDatabaseHas('keuangans', ['keterangan' => 'uji-keu-kas-umum']);

        // Tab proyek hanya memuat transaksinya sendiri
        $this->actingAs($this->admin)->get('/admin/keuangan?tab='.$tab->id)
            ->assertOk()
            ->assertSee('100.000', false)
            ->assertDontSee('uji-keu-kas-umum', false);

        // Kas Umum (tanpa ?tab) hanya memuat transaksi tab_id NULL
        $this->actingAs($this->admin)->get('/admin/keuangan')
            ->assertOk()
            ->assertSee('uji-keu-kas-umum', false)
            ->assertDontSee('uji-keu-tab-masuk', false);
    }

    public function test_buat_rename_dan_hapus_tab_dengan_pindah_transaksi_ke_kas_umum(): void
    {
        // Buat tab
        $res = $this->actingAs($this->admin)->postJson('/admin/keuangan/tabs', ['nama' => 'uji-tab-farm'])
            ->assertOk()
            ->assertJson(['ok' => true]);
        $tabId = $res->json('id');
        $this->assertDatabaseHas('financial_tabs', ['id' => $tabId, 'nama' => 'uji-tab-farm']);

        // Rename
        $this->actingAs($this->admin)->putJson("/admin/keuangan/tabs/{$tabId}", ['nama' => 'uji-tab-farm-2'])
            ->assertOk();
        $this->assertDatabaseHas('financial_tabs', ['id' => $tabId, 'nama' => 'uji-tab-farm-2']);

        // Transaksi di dalam tab lalu tab dihapus → transaksi pindah Kas Umum
        Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Operasional', 'financial_tab_id' => $tabId, 'jumlah' => 75000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-tab-hapus']);
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/tabs/{$tabId}")->assertOk();

        $this->assertDatabaseMissing('financial_tabs', ['id' => $tabId]);
        $this->assertDatabaseHas('keuangans', ['keterangan' => 'uji-keu-tab-hapus', 'financial_tab_id' => null]);
    }

    public function test_target_anggaran_biaya_tambahan_dan_total_budget(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-budget']);

        // Target anggaran utama
        $this->actingAs($this->admin)->putJson("/admin/keuangan/tabs/{$tab->id}/budget", ['target_anggaran' => 200000000])
            ->assertOk()
            ->assertJsonPath('total_budget', 200000000);

        // Biaya tambahan: tambah → edit → total berubah
        $res = $this->actingAs($this->admin)->postJson("/admin/keuangan/tabs/{$tab->id}/costs", [
            'nama' => 'uji-biaya-pondasi', 'nominal' => 50000000,
        ])->assertOk()->assertJsonPath('total_budget', 250000000);
        $costId = $res->json('id');

        $this->actingAs($this->admin)->putJson("/admin/keuangan/costs/{$costId}", [
            'nama' => 'uji-biaya-pondasi-2', 'nominal' => 60000000,
        ])->assertOk()->assertJsonPath('total_budget', 260000000);

        // Pengeluaran di tab → SISA BUDGET = 260jt - 10jt
        Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Pembangunan', 'financial_tab_id' => $tab->id, 'jumlah' => 10000000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-budget-keluar']);

        $this->actingAs($this->admin)->get('/admin/keuangan?tab='.$tab->id)
            ->assertOk()
            ->assertSee('Total Budget', false)
            ->assertSee('Sisa Budget', false)
            ->assertSee('260.000.000', false)
            ->assertSee('250.000.000', false);

        // Hapus biaya tambahan
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/costs/{$costId}")->assertOk();
        $this->assertDatabaseMissing('financial_tab_costs', ['id' => $costId]);
    }

    public function test_validasi_tab_dan_biaya(): void
    {
        // Nama tab wajib
        $this->actingAs($this->admin)->postJson('/admin/keuangan/tabs', [])->assertStatus(422);
        // Nama tab unik
        FinancialTab::create(['nama' => 'uji-tab-dup']);
        $this->actingAs($this->admin)->postJson('/admin/keuangan/tabs', ['nama' => 'uji-tab-dup'])->assertStatus(422);
        // Nominal biaya tidak boleh minus
        $tab = FinancialTab::create(['nama' => 'uji-tab-val']);
        $this->actingAs($this->admin)->postJson("/admin/keuangan/tabs/{$tab->id}/costs", ['nama' => 'x', 'nominal' => -5])->assertStatus(422);
        // Tab harus ada
        $this->actingAs($this->admin)->postJson('/admin/keuangan', [
            'tipe' => 'masuk', 'kategori' => 'Lainnya', 'jumlah' => 1000,
            'tanggal' => '2026-11-01', 'financial_tab_id' => 999999,
        ])->assertStatus(422);
    }

    public function test_export_pdf_tab_proyek_memuat_budget(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-pdf', 'target_anggaran' => 100000000]);
        $this->actingAs($this->admin)->postJson("/admin/keuangan/tabs/{$tab->id}/costs", [
            'nama' => 'uji-biaya-kompon', 'nominal' => 20000000,
        ])->assertOk();

        $this->actingAs($this->admin)->get('/admin/keuangan/export/pdf?tab='.$tab->id)
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }
}
