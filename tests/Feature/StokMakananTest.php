<?php

namespace Tests\Feature;

use App\Models\StokMakanan;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Stok makanan burung (biji-bijian & pellet): estimasi habis + prediksi bulan beli ulang.
 */
class StokMakananTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-stok-test@4putra.test'],
            ['name' => 'Admin Stok', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        DB::table('stok_makanans')->where('nama', 'like', 'uji-stok%')->delete();
        User::where('email', 'admin-stok-test@4putra.test')->delete();
        User::where('email', 'user-stok-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_menu_terbuka_role_user_dan_terproteksi_login(): void
    {
        $this->get('/admin/stok-makanan')->assertRedirect(route('login'));

        $roleUser = User::updateOrCreate(
            ['email' => 'user-stok-test@4putra.test'],
            ['name' => 'Role User', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        // Role user boleh memantau stok makanan (full akses menu monitoring).
        $this->actingAs($roleUser)->get('/admin/stok-makanan')->assertOk();
        $this->actingAs($this->admin)->get('/admin/stok-makanan')->assertOk();
    }

    public function test_tambah_stok_divalidasi_dan_tersimpan(): void
    {
        // Konsumsi 0 → 422 (agar estimasi tidak membagi nol)
        $this->actingAs($this->admin)
            ->postJson('/admin/stok-makanan', [
                'nama' => 'uji-stok-sunseed', 'jumlah_stok' => 50, 'satuan' => 'kg', 'konsumsi_per_hari' => 0,
            ])->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/stok-makanan', [
                'nama' => 'uji-stok-sunseed', 'jumlah_stok' => 50, 'satuan' => 'kg', 'konsumsi_per_hari' => 0.5,
            ])->assertOk();

        $this->assertDatabaseHas('stok_makanans', [
            'nama' => 'uji-stok-sunseed', 'jumlah_stok' => '50.00', 'konsumsi_per_hari' => '0.50',
        ]);
    }

    public function test_estimasi_habis_dan_prediksi_beli_ulang_dihitung_otomatis(): void
    {
        // 50 kg / 0,5 kg per hari = 100 hari (~3,3 bulan) → tidak perlu beli segera
        $cukup = StokMakanan::create([
            'nama' => 'uji-stok-sunseed', 'jumlah_stok' => 50, 'satuan' => 'kg', 'konsumsi_per_hari' => 0.5,
        ]);
        $this->assertSame(100, $cukup->estimasi_hari);
        $this->assertFalse($cukup->perlu_beli);

        // 5 kg / 1 kg per hari = 5 hari → perlu beli segera
        $kritis = StokMakanan::create([
            'nama' => 'uji-stok-pellet', 'jumlah_stok' => 5, 'satuan' => 'kg', 'konsumsi_per_hari' => 1,
        ]);
        $this->assertSame(5, $kritis->estimasi_hari);
        $this->assertTrue($kritis->perlu_beli);
        $this->assertEquals(now()->addDays(5)->toDateString(), $kritis->tanggal_beli_ulang->toDateString());
    }

    public function test_update_stok_setelah_beli(): void
    {
        $stok = StokMakanan::create([
            'nama' => 'uji-stok-sunseed', 'jumlah_stok' => 5, 'satuan' => 'kg', 'konsumsi_per_hari' => 1,
        ]);

        $this->actingAs($this->admin)
            ->putJson("/admin/stok-makanan/{$stok->id}", [
                'jumlah_stok' => 40, 'konsumsi_per_hari' => 0.5, 'satuan' => 'kg',
            ])->assertOk();

        $this->assertDatabaseHas('stok_makanans', ['id' => $stok->id, 'jumlah_stok' => '40.00']);
    }

    public function test_hapus_stok(): void
    {
        $stok = StokMakanan::create([
            'nama' => 'uji-stok-hapus', 'jumlah_stok' => 10, 'satuan' => 'kg', 'konsumsi_per_hari' => 1,
        ]);
        $this->actingAs($this->admin)->deleteJson("/admin/stok-makanan/{$stok->id}")->assertOk();
        $this->assertDatabaseMissing('stok_makanans', ['id' => $stok->id]);
    }

    public function test_export_pdf_menghasilkan_unduhan(): void
    {
        StokMakanan::create(['nama' => 'uji-stok-sunseed', 'jumlah_stok' => 50, 'satuan' => 'kg', 'konsumsi_per_hari' => 1]);

        $this->get('/admin/stok-makanan/export/pdf')->assertRedirect(route('login'));

        $response = $this->actingAs($this->admin)->get('/admin/stok-makanan/export/pdf');
        $response->assertOk();
        $this->assertStringContainsString('application/pdf', (string) $response->headers->get('Content-Type'));
    }
}
