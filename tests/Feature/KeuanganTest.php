<?php

namespace Tests\Feature;

use App\Models\Keuangan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Keuangan farm: pemasukan, pengeluaran, gaji, dan budget pembangunan.
 * Uang krusial → jumlah selalu dari input server-side yang divalidasi + audit log.
 */
class KeuanganTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-keu-test@4putra.test'],
            ['name' => 'Admin Keu', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        DB::table('keuangans')->where('keterangan', 'like', 'uji-keu%')->delete();
        User::where('email', 'admin-keu-test@4putra.test')->delete();
        User::where('email', 'user-keu-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_hanya_admin_yang_bisa_mengakses(): void
    {
        $this->get('/admin/keuangan')->assertRedirect(route('login'));

        $bukanAdmin = User::updateOrCreate(
            ['email' => 'user-keu-test@4putra.test'],
            ['name' => 'Bukan Admin', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        $this->actingAs($bukanAdmin)->get('/admin/keuangan')->assertStatus(403);
        $this->actingAs($this->admin)->get('/admin/keuangan')->assertOk();
    }

    public function test_tambah_transaksi_divalidasi_dan_tersimpan(): void
    {
        // Tipe tidak valid → 422
        $this->actingAs($this->admin)
            ->postJson('/admin/keuangan', [
                'tipe' => 'urpal', 'kategori' => 'Gaji', 'jumlah' => 100000,
                'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-x',
            ])->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/keuangan', [
                'tipe' => 'masuk', 'kategori' => 'Pembangunan', 'jumlah' => 50000000,
                'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-setor pembangunan farm',
            ])->assertOk();

        $this->assertDatabaseHas('keuangans', [
            'tipe' => 'masuk', 'kategori' => 'Pembangunan', 'jumlah' => '50000000.00',
        ]);
    }

    public function test_ringkasan_masuk_keluar_saldo_dihitung_dari_database(): void
    {
        Keuangan::create(['tipe' => 'masuk', 'kategori' => 'Pembangunan', 'jumlah' => 200000000, 'tanggal' => '2026-11-01', 'keterangan' => 'uji-keu-dana bos']);
        Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Gaji', 'jumlah' => 3000000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-gaji pekerja']);
        Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Operasional', 'jumlah' => 1000000, 'tanggal' => '2026-11-06', 'keterangan' => 'uji-keu-pakan']);

        $res = $this->actingAs($this->admin)->get('/admin/keuangan');
        $res->assertOk();
        $res->assertSee('200.000.000', false); // total masuk dgn format id-ID (200 jt dana pembangunan)
        $res->assertSee('Saldo', false);

        DB::table('keuangans')->where('keterangan', 'like', 'uji-keu%')->delete();
    }

    public function test_hapus_transaksi(): void
    {
        $trx = Keuangan::create(['tipe' => 'keluar', 'kategori' => 'Gaji', 'jumlah' => 1500000, 'tanggal' => '2026-11-05', 'keterangan' => 'uji-keu-hapus']);
        $this->actingAs($this->admin)->deleteJson("/admin/keuangan/{$trx->id}")->assertOk();
        $this->assertDatabaseMissing('keuangans', ['id' => $trx->id]);
    }
}
