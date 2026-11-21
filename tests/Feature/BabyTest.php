<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Keuangan;
use App\Models\Kandang;
use App\Models\Penjualan;
use App\Models\Telur;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Menu Baby (loloh): konversi telur menetas → baby, penjualan masuk Kas Umum,
 * dan pemisahan domain (baby tidak masuk pool kandang/aviary).
 */
class BabyTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-baby-test@4putra.test'],
            ['name' => 'Admin Baby', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        Penjualan::where('nama_burung', 'like', 'uji-baby%')->delete();
        Keuangan::where('keterangan', 'like', 'Penjualan burung uji-baby%')->delete();
        Burung::where('nama', 'like', 'uji-baby%')->delete();
        Telur::where('diambil_oleh', 'uji-pekerja-baby')->delete();
        Kandang::where('blok', 'Z')->delete();
        User::where('email', 'admin-baby-test@4putra.test')->delete();
        User::where('email', 'user-baby-test@4putra.test')->delete();
        parent::tearDown();
    }

    /** Telur menetas dengan induk tunggal (uji-kandang-babyparent) + umur menetas bisa diatur. */
    private function telurMenetas(int $umurHari): Telur
    {
        $induk = Burung::create(['nama' => 'uji-baby-induk', 'spesies' => 'Camelot Macaw', 'fase' => 'dewasa']);

        return Telur::create([
            'burung_id' => $induk->id,
            'sesi' => 1,
            'jumlah' => 1,
            'butir_ke' => 1,
            'tanggal_diambil' => now('Asia/Jakarta')->subDays(100)->toDateString(),
            'diambil_oleh' => 'uji-pekerja-baby',
            'kondisi' => 'Kondisi telur baik',
            'status' => 'menetas',
            'tanggal_menetas' => now('Asia/Jakarta')->subDays($umurHari)->toDateString(),
        ]);
    }

    public function test_menu_baby_terbuka_role_user_dan_terproteksi_login(): void
    {
        $this->get('/admin/baby')->assertRedirect(route('login'));

        $roleUser = User::updateOrCreate(
            ['email' => 'user-baby-test@4putra.test'],
            ['name' => 'Role User', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        $this->actingAs($roleUser)->get('/admin/baby')->assertOk();
        $roleUser->delete();
    }

    public function test_konversi_telur_menetas_ke_baby_membuat_burung_dan_silsilah(): void
    {
        $telur = $this->telurMenetas(31);

        $this->actingAs($this->admin)
            ->postJson("/admin/telur/{$telur->id}/ke-baby", [
                'baby_nama' => 'uji-baby-pertama',
                'baby_ring' => 'RG-UJI-001',
                'baby_gender' => 'jantan',
                'baby_dna' => 'DNA-UJI-001',
            ])->assertOk();

        $baby = Burung::where('nama', 'uji-baby-pertama')->first();
        $this->assertNotNull($baby);
        $this->assertEquals($telur->id, $baby->telur_id);
        $this->assertEquals($telur->tanggal_menetas->toDateString(), $baby->tanggal_lahir->toDateString());
        $this->assertEquals('anakan', $baby->fase);
        $this->assertStringContainsString('uji-baby-induk', $baby->induk_label);
        $this->assertEquals('dilepas', $telur->fresh()->status);

        $baby->delete();
        $telur->burung->delete();
    }

    public function test_konversi_boleh_sebelum_30_hari_tanpa_batas_umur(): void
    {
        $telur = $this->telurMenetas(10);

        $this->actingAs($this->admin)
            ->postJson("/admin/telur/{$telur->id}/ke-baby", [
                'baby_nama' => 'uji-baby-muda',
                'baby_ring' => 'RG-UJI-002',
            ])->assertOk();

        // Ring kini opsional — baby tetap dibuat tanpa ring
        $this->assertDatabaseHas('burungs', ['nama' => 'uji-baby-muda', 'nomor_ring' => 'RG-UJI-002']);
    }

    public function test_konversi_ditolak_bukan_butir_menetas(): void
    {
        $telur = $this->telurMenetas(31);
        $telur->update(['status' => 'diinkubasi', 'tanggal_menetas' => null]);

        $this->actingAs($this->admin)
            ->postJson("/admin/telur/{$telur->id}/ke-baby", [
                'baby_nama' => 'uji-baby-ditolak',
                'baby_ring' => 'RG-UJI-003',
            ])->assertStatus(422);
    }

    public function test_update_dilepas_langsung_pada_butir_menetas_ditolak(): void
    {
        $telur = $this->telurMenetas(31);

        $this->actingAs($this->admin)
            ->putJson("/admin/telur/{$telur->id}", [
                'kondisi' => 'Kondisi telur baik',
                'status' => 'dilepas',
            ])->assertStatus(422);
    }

    public function test_baby_muncul_otomatis_setelah_1_bulan_saat_halaman_telur_dibuka(): void
    {
        // Butir menetas 31 hari, belum dikonversi manual → GET halaman telur
        // otomatis membuat baby bawaan (nama otomatis, ring/gender kosong).
        $telur = $this->telurMenetas(31);
        $telur->load('burung.kandang');
        $telur->burung->update([
            'kandang_id' => Kandang::create(['blok' => 'Z', 'nomor' => 990, 'tipe' => 'kecil'])->id,
            'nomor_ring' => 'RG-UJI-INDUK',
        ]);

        $this->actingAs($this->admin)->get('/admin/telur')->assertOk();

        $baby = Burung::where('telur_id', $telur->id)->first();
        $this->assertNotNull($baby, 'Baby otomatis harus dibuat');
        $this->assertSame('anakan', $baby->fase);
        $this->assertNull($baby->nomor_ring);
        $this->assertStringContainsString('Baby', $baby->nama);
        $this->assertStringContainsString('Blok Z', $baby->asal_label);
        $this->assertStringContainsString('Ring', $baby->induk_label);
        $this->assertSame('dilepas', $telur->fresh()->status);

        $baby->delete();
    }

    public function test_jual_baby_uang_masuk_kas_umum_dan_snapshot_tersimpan(): void
    {
        $baby = Burung::create([
            'nama' => 'uji-baby-jual',
            'spesies' => 'Camelot Macaw',
            'fase' => 'anakan',
            'nomor_ring' => 'RG-UJI-JUAL',
            'tanggal_lahir' => now('Asia/Jakarta')->subDays(45)->toDateString(),
            'induk_label' => 'uji-baby-induk (Camelot Macaw - Jantan)',
        ]);

        $this->actingAs($this->admin)
            ->postJson('/admin/baby/jual', [
                'burung_id' => $baby->id,
                'tanggal_terjual' => now('Asia/Jakarta')->toDateString(),
                'harga' => 2500000,
            ])->assertOk()
            ->assertJsonFragment(['ok' => true]);

        $penjualan = Penjualan::where('nama_burung', 'uji-baby-jual')->first();
        $this->assertNotNull($penjualan);
        $this->assertEquals('RG-UJI-JUAL', $penjualan->nomor_ring);
        $this->assertStringContainsString('uji-baby-induk', $penjualan->induk_label);
        $this->assertNotNull($penjualan->usia_hari);

        // Uang otomatis masuk Kas Umum (keuangans: financial_tab_id NULL)
        $kas = Keuangan::where('keterangan', 'like', 'Penjualan burung uji-baby-jual%')->first();
        $this->assertNotNull($kas);
        $this->assertEquals('masuk', $kas->tipe);
        $this->assertEquals('Penjualan', $kas->kategori);
        $this->assertNull($kas->financial_tab_id);
        $this->assertEquals(2500000, (float) $kas->jumlah);
        $this->assertEquals($kas->id, $penjualan->keuangan_id);

        // Baris burung dihapus, riwayat aman di snapshot
        $this->assertDatabaseMissing('burungs', ['id' => $baby->id]);
    }

    public function test_jual_ditolak_burung_masih_di_kandang(): void
    {
        $kandang = Kandang::create(['blok' => 'Z', 'nomor' => 991, 'tipe' => 'kecil']);
        $burung = Burung::create([
            'nama' => 'uji-baby-dikandang',
            'spesies' => 'Sun Conure',
            'fase' => 'anakan',
            'kandang_id' => $kandang->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson('/admin/baby/jual', [
                'burung_id' => $burung->id,
                'tanggal_terjual' => now('Asia/Jakarta')->toDateString(),
                'harga' => 1000000,
            ])->assertStatus(422)
            ->assertJsonFragment(['ok' => false]);

        $this->assertDatabaseHas('burungs', ['id' => $burung->id]);
        $burung->delete();
    }

    public function test_baby_tidak_muncul_di_pool_kandang_dan_aviary(): void
    {
        $baby = Burung::create(['nama' => 'uji-baby-pool', 'spesies' => 'Sun Conure', 'fase' => 'anakan']);
        $dewasa = Burung::create(['nama' => 'uji-baby-dewasa', 'spesies' => 'Sun Conure', 'fase' => 'dewasa']);

        $this->actingAs($this->admin)->get('/admin/kandang')
            ->assertOk()
            ->assertSee('uji-baby-dewasa', false)
            ->assertDontSee('uji-baby-pool', false);
        $this->actingAs($this->admin)->get('/admin/aviary')
            ->assertOk()
            ->assertDontSee('uji-baby-pool', false);
        $this->actingAs($this->admin)->get('/admin/baby')
            ->assertOk()
            ->assertSee('uji-baby-pool', false);

        $baby->delete();
        $dewasa->delete();
    }

    public function test_edit_baby_ring_gender_dna(): void
    {
        $baby = Burung::create(['nama' => 'uji-baby-edit', 'spesies' => 'Sun Conure', 'fase' => 'anakan']);

        $this->actingAs($this->admin)
            ->putJson("/admin/baby/{$baby->id}", [
                'nama' => 'uji-baby-edit',
                'spesies' => 'Sun Conure',
                'fase' => 'anakan',
                'gender' => 'betina',
                'nomor_ring' => 'RG-UJI-EDIT',
                'nomor_dna' => 'DNA-UJI-EDIT',
            ])->assertOk();

        $this->assertDatabaseHas('burungs', ['id' => $baby->id, 'nomor_ring' => 'RG-UJI-EDIT', 'nomor_dna' => 'DNA-UJI-EDIT']);
        $baby->delete();
    }
}
