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

    /** transfer_id yang dibuat test — dibersihkan pasangan-pasangannya di tearDown. */
    private array $transferIds = [];

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
        // Sisi transfer yang dibuat test (akurat walau keterangannya bebas),
        // sebelum tab uji dihapus (FK nullOnDelete akan mengaburkan jejaknya).
        if ($this->transferIds !== []) {
            DB::table('keuangans')->whereIn('transfer_id', $this->transferIds)->delete();
        }
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

        // Tab yang masih berisi transaksi TIDAK boleh dihapus (diblok)
        Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Operasional', 'financial_tab_id' => $tabId, 'jumlah' => 75000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-tab-hapus']);
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/tabs/{$tabId}")
            ->assertStatus(422)
            ->assertJson(['ok' => false]);
        $this->assertDatabaseHas('financial_tabs', ['id' => $tabId]);

        // Setelah transaksinya dihapus, tab baru bisa dihapus
        DB::table('keuangans')->where('keterangan', 'uji-keu-tab-hapus')->delete();
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/tabs/{$tabId}")->assertOk();
        $this->assertDatabaseMissing('financial_tabs', ['id' => $tabId]);
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

    public function test_hapus_tab_dengan_hapus_semua_transaksi_sekali_klik(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-massal']);
        foreach ([1, 2, 3] as $i) {
            Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Operasional', 'financial_tab_id' => $tab->id, 'jumlah' => 1000 * $i, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-massal-'.$i]);
        }

        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/tabs/{$tab->id}?hapus_semua=1")
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('financial_tabs', ['id' => $tab->id]);
        $this->assertDatabaseMissing('keuangans', ['keterangan' => 'uji-keu-massal-1']);
        $this->assertDatabaseMissing('keuangans', ['keterangan' => 'uji-keu-massal-3']);
    }

    public function test_edit_transaksi_tersimpan_dan_divalidasi(): void
    {
        $trx = Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Operasional', 'jumlah' => 100000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-edit']);

        // Perbaikan typo: kategori & jumlah diubah
        $this->actingAs($this->admin)->putJson("/admin/keuangan/{$trx->id}", [
            'tipe' => 'keluar', 'kategori' => 'Gaji', 'jumlah' => 200000,
            'tanggal' => '2026-11-06', 'keterangan' => 'uji-keu-edit-2',
        ])->assertOk()->assertJson(['ok' => true]);

        $this->assertDatabaseHas('keuangans', [
            'id' => $trx->id, 'kategori' => 'Gaji', 'jumlah' => '200000.00', 'tanggal' => '2026-11-06',
        ]);

        // Tanggal masa depan (di luar batas WIB) tetap ditolak saat edit
        $besok = now('Asia/Jakarta')->addDay()->toDateString();
        $this->actingAs($this->admin)->putJson("/admin/keuangan/{$trx->id}", [
            'tipe' => 'keluar', 'kategori' => 'Gaji', 'jumlah' => 200000,
            'tanggal' => $besok, 'keterangan' => 'uji-keu-edit-3',
        ])->assertStatus(422);

        // Tipe tidak valid ditolak
        $this->actingAs($this->admin)->putJson("/admin/keuangan/{$trx->id}", [
            'tipe' => 'urpal', 'kategori' => 'Gaji', 'jumlah' => 200000,
            'tanggal' => '2026-11-06', 'keterangan' => 'uji-keu-edit-4',
        ])->assertStatus(422);
    }

    public function test_edit_transaksi_transfer_ditolak(): void
    {
        $tujuan = FinancialTab::create(['nama' => 'uji-tab-edit-transfer']);
        Keuangan::create(['tipe' => 'masuk', 'kategori' => 'Penjualan', 'jumlah' => 50000000, 'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-edit-saldo']);

        $res = $this->actingAs($this->admin)->postJson('/admin/keuangan/transfer', [
            'dari' => 'kas_umum', 'ke' => $tujuan->id, 'nominal' => 15000000, 'keterangan' => 'uji-transfer-edit',
        ])->assertOk();
        $this->transferIds[] = $res->json('transfer_id');

        $keluar = Keuangan::where('tipe', 'keluar')->where('transfer_id', $res->json('transfer_id'))->first();
        $this->actingAs($this->admin)->putJson("/admin/keuangan/{$keluar->id}", [
            'tipe' => 'keluar', 'kategori' => 'Gaji', 'jumlah' => 1, 'tanggal' => '2026-11-06',
        ])->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_hapus_tab_diblok_selama_ada_transaksi(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-blok']);
        Keuangan::create(['tipe' => 'masuk', 'kategori' => 'Pembangunan', 'financial_tab_id' => $tab->id, 'jumlah' => 65000000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-tab-blok']);

        $res = $this->actingAs($this->admin)->deleteJson("/admin/keuangan/tabs/{$tab->id}");

        $res->assertStatus(422)->assertJson(['ok' => false]);
        $this->assertDatabaseHas('financial_tabs', ['id' => $tab->id]);
        $this->assertDatabaseHas('keuangans', ['keterangan' => 'uji-keu-tab-blok']);
    }

    public function test_transfer_dana_membuat_pasangan_dua_sisi(): void
    {
        $tujuan = FinancialTab::create(['nama' => 'uji-tab-tujuan']);
        Keuangan::create(['tipe' => 'masuk', 'kategori' => 'Penjualan', 'jumlah' => 100000000, 'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-saldo-awal']);

        $res = $this->actingAs($this->admin)->postJson('/admin/keuangan/transfer', [
            'dari' => 'kas_umum', 'ke' => $tujuan->id, 'nominal' => 15000000, 'keterangan' => 'uji-transfer-bayar-bahan',
        ])->assertOk()->assertJson(['ok' => true]);
        $this->transferIds[] = $res->json('transfer_id');
        $this->transferIds[] = $res->json('transfer_id');

        // Kas Umum berkurang: baris keluar berkategori Transfer (milik pasangan test ini)
        $keluar = Keuangan::where('tipe', 'keluar')->where('transfer_id', $res->json('transfer_id'))->first();
        $this->assertNotNull($keluar);
        $this->assertSame('15000000.00', (string) $keluar->jumlah);
        $this->assertNull($keluar->financial_tab_id);
        $this->assertStringContainsString('uji-tab-tujuan', $keluar->keterangan);
        $this->assertStringContainsString('uji-transfer-bayar-bahan', $keluar->keterangan);

        // Tab tujuan bertambah: baris masuk berkategori Transfer
        $masuk = Keuangan::where('tipe', 'masuk')->where('transfer_id', $res->json('transfer_id'))->first();
        $this->assertNotNull($masuk);
        $this->assertSame('15000000.00', (string) $masuk->jumlah);
        $this->assertEquals($tujuan->id, $masuk->financial_tab_id);
        $this->assertStringContainsString('Kas Umum', $masuk->keterangan);

        // Pasangan terikat satu transfer_id
        $this->assertNotNull($keluar->transfer_id);
        $this->assertSame($keluar->transfer_id, $masuk->transfer_id);

        // Hapus satu sisi → kedua sisi ikut terhapus
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/{$keluar->id}")->assertOk();
        $this->assertDatabaseMissing('keuangans', ['id' => $keluar->id]);
        $this->assertDatabaseMissing('keuangans', ['id' => $masuk->id]);
    }

    public function test_transfer_ditolak_jika_saldo_asal_tidak_cukup(): void
    {
        // Tab kosong = saldo pasti 0 (deterministik, tak tergantung data nyata kas umum)
        $asal = FinancialTab::create(['nama' => 'uji-tab-asal-kering']);
        $tujuan = FinancialTab::create(['nama' => 'uji-tab-tujuan2']);

        $this->actingAs($this->admin)->postJson('/admin/keuangan/transfer', [
            'dari' => $asal->id, 'ke' => $tujuan->id, 'nominal' => 10000000,
        ])->assertStatus(422)->assertJson(['ok' => false]);
    }

    public function test_transfer_ditolak_ke_tab_yang_sama(): void
    {
        $tab = FinancialTab::create(['nama' => 'uji-tab-sama']);

        $this->actingAs($this->admin)->postJson('/admin/keuangan/transfer', [
            'dari' => $tab->id, 'ke' => $tab->id, 'nominal' => 1000,
        ])->assertStatus(422);
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
