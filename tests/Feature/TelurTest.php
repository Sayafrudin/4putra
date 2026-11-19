<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Kandang;
use App\Models\Telur;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Monitoring telur: sesi bertelur, perebut, usia harian, menetas → incubator → siap dilepas (±3 bulan).
 */
class TelurTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-telur-test@4putra.test'],
            ['name' => 'Admin Telur', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        DB::table('telurs')->whereIn('diambil_oleh', ['uji-pekerja-telur'])->delete();
        Burung::where('nama', 'like', 'uji-telur%')->delete();
        Kandang::where('blok', 'A')->where('nomor', '>=', 990)->delete();
        User::where('email', 'admin-telur-test@4putra.test')->delete();
        User::where('email', 'user-telur-test@4putra.test')->delete();
        parent::tearDown();
    }

    private function burungUji(): Burung
    {
        return Burung::create(['nama' => 'uji-telur-camelot', 'spesies' => 'Camelot Macaw', 'fase' => 'dewasa']);
    }

    /** Kandang pasangan: 2 burung dewasa dalam satu kandang (blok 1 huruf, nomor 990+ agar bebas tabrakan data nyata). */
    private function kandangPasanganUji(): array
    {
        $kandang = Kandang::create(['blok' => 'A', 'nomor' => 990, 'tipe' => 'medium']);
        $jantan = Burung::create(['nama' => 'uji-telur-jantan', 'spesies' => 'BNG Macaw', 'fase' => 'dewasa', 'kandang_id' => $kandang->id]);
        $betina = Burung::create(['nama' => 'uji-telur-betina', 'spesies' => 'Military Macaw', 'fase' => 'dewasa', 'kandang_id' => $kandang->id]);

        return [$kandang, $jantan, $betina];
    }

    public function test_menu_terbuka_role_user_dan_terproteksi_login(): void
    {
        $this->get('/admin/telur')->assertRedirect(route('login'));

        $roleUser = User::updateOrCreate(
            ['email' => 'user-telur-test@4putra.test'],
            ['name' => 'Role User', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        // Role user boleh memantau telur (full akses menu monitoring).
        $this->actingAs($roleUser)->get('/admin/telur')->assertOk();
        $this->actingAs($this->admin)->get('/admin/telur')->assertOk();
    }

    public function test_tambah_telur_divalidasi_dan_tersimpan(): void
    {
        [$kandang, $jantan, $betina] = $this->kandangPasanganUji();

        $this->actingAs($this->admin)
            ->postJson('/admin/telur', ['diambil_oleh' => 'uji-pekerja-telur'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $betina->id,
                'burung_pasangan_id' => $jantan->id,
                'sesi' => 1,
                'jumlah' => 2,
                'tanggal_diambil' => now()->subDays(15)->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Kondisi telur baik',
            ])->assertOk();

        $this->assertDatabaseHas('telurs', [
            'burung_id' => $betina->id,
            'burung_pasangan_id' => $jantan->id,
            'sesi' => 1,
            'jumlah' => 2,
            'status' => 'diinkubasi',
        ]);
    }

    public function test_jumlah_butir_dipecah_jadi_satu_baris_per_butir(): void
    {
        // 1 baris = 1 butir: jumlah=2 membuat 2 baris (butir_ke 1 & 2) —
        // tiap butir termonitor sendiri (status/tanggal menetas bisa beda).
        [$kandang, $jantan, $betina] = $this->kandangPasanganUji();

        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $betina->id,
                'burung_pasangan_id' => $jantan->id,
                'sesi' => 3,
                'jumlah' => 2,
                'tanggal_diambil' => now()->subDays(5)->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Kondisi telur baik',
            ])->assertOk();

        $this->assertSame(2, DB::table('telurs')->where('sesi', 3)->where('diambil_oleh', 'uji-pekerja-telur')->count());
        $this->assertSame(1, DB::table('telurs')->where('sesi', 3)->where('diambil_oleh', 'uji-pekerja-telur')->where('butir_ke', 1)->count());
        $this->assertSame(1, DB::table('telurs')->where('sesi', 3)->where('diambil_oleh', 'uji-pekerja-telur')->where('butir_ke', 2)->count());
        $this->assertSame(2, DB::table('telurs')->where('sesi', 3)->where('diambil_oleh', 'uji-pekerja-telur')->where('jumlah', 2)->count());
    }

    public function test_tanggal_hari_ini_diterima_pada_dini_hari_wib(): void
    {
        // Simulasi produksi (UTC): 18 Nov 01:00 WIB = 17 Nov 18:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-17 18:00', 'UTC'));
        $tzLama = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            [$kandang, $jantan, $betina] = $this->kandangPasanganUji();

            $this->actingAs($this->admin)
                ->postJson('/admin/telur', [
                    'burung_id' => $betina->id,
                    'burung_pasangan_id' => $jantan->id,
                    'sesi' => 1,
                    'jumlah' => 1,
                    'tanggal_diambil' => '2026-11-18',
                    'diambil_oleh' => 'uji-pekerja-telur',
                    'kondisi' => 'Kondisi telur baik',
                ])->assertOk();

            $this->assertDatabaseHas('telurs', ['burung_id' => $betina->id, 'tanggal_diambil' => '2026-11-18']);
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($tzLama);
        }
    }

    public function test_pasangan_ditolak_jika_bukan_dua_burung_dewasa_satu_kandang(): void
    {
        [$kandang, $jantan, $betina] = $this->kandangPasanganUji();
        $burungLain = $this->burungUji(); // dewasa, tanpa kandang

        // Pasangan = burung yang sama → ditolak
        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $jantan->id,
                'burung_pasangan_id' => $jantan->id,
                'sesi' => 1,
                'jumlah' => 1,
                'tanggal_diambil' => now()->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Baik',
            ])->assertStatus(422);

        // Pasangan beda kandang → ditolak
        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $jantan->id,
                'burung_pasangan_id' => $burungLain->id,
                'sesi' => 1,
                'jumlah' => 1,
                'tanggal_diambil' => now()->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Baik',
            ])->assertStatus(422);

        // Tanpa pasangan → ditolak (telur wajib dicatat per pasangan)
        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $jantan->id,
                'sesi' => 1,
                'jumlah' => 1,
                'tanggal_diambil' => now()->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Baik',
            ])->assertStatus(422);

        // Burung anakan tidak boleh jadi induk
        $anakan = Burung::create(['nama' => 'uji-telur-anak', 'spesies' => 'Sun Conure', 'fase' => 'anakan', 'kandang_id' => $kandang->id]);
        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $jantan->id,
                'burung_pasangan_id' => $anakan->id,
                'sesi' => 1,
                'jumlah' => 1,
                'tanggal_diambil' => now()->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Baik',
            ])->assertStatus(422);
    }

    public function test_index_menyajikan_daftar_kandang_pasangan_dan_label_pasangan(): void
    {
        [$kandang, $jantan, $betina] = $this->kandangPasanganUji();
        $telur = Telur::create([
            'burung_id' => $betina->id,
            'burung_pasangan_id' => $jantan->id,
            'sesi' => 1,
            'jumlah' => 2,
            'tanggal_diambil' => now()->subDays(10)->toDateString(),
            'diambil_oleh' => 'uji-pekerja-telur',
            'kondisi' => 'Kondisi telur baik',
        ]);

        $res = $this->actingAs($this->admin)->get('/admin/telur')->assertOk();

        // Dropdown kandang pasangan: kandang berisi tepat 2 dewasa muncul, kandang 1 dewasa tidak
        $res->assertSee('A-990');
        $res->assertSee('uji-telur-jantan');
        $res->assertSee('uji-telur-betina');
        // Tabel: catatan berpasangan tampil sebagai "burung1 × burung2"
        $res->assertSee('×');
    }

    public function test_usia_telur_dihitung_dari_tanggal_diambil(): void
    {
        $burung = $this->burungUji();
        $telur = Telur::create([
            'burung_id' => $burung->id,
            'sesi' => 1,
            'jumlah' => 2,
            'tanggal_diambil' => now()->subDays(15)->toDateString(),
            'diambil_oleh' => 'uji-pekerja-telur',
            'kondisi' => 'Kondisi telur baik',
        ]);

        $this->assertSame(15, $telur->usia_hari);
        $this->assertFalse($telur->siap_dilepas);

        // Menetas 5 hari lalu → umur anak 5 hari, belum siap dilepas (butuh ±90 hari)
        $telur->update(['status' => 'menetas', 'tanggal_menetas' => now()->subDays(5)->toDateString()]);
        $this->assertSame(5, $telur->usia_menetas_hari);
        $this->assertFalse($telur->siap_dilepas);

        // Menetas 100 hari lalu → siap dilepas
        $telur->update(['tanggal_menetas' => now()->subDays(100)->toDateString()]);
        $this->assertTrue($telur->siap_dilepas);
    }

    public function test_update_kondisi_dan_menetas(): void
    {
        $burung = $this->burungUji();
        $telur = Telur::create([
            'burung_id' => $burung->id,
            'sesi' => 1,
            'jumlah' => 2,
            'tanggal_diambil' => now()->subDays(20)->toDateString(),
            'diambil_oleh' => 'uji-pekerja-telur',
            'kondisi' => 'Kondisi telur baik',
        ]);

        $this->actingAs($this->admin)
            ->putJson("/admin/telur/{$telur->id}", [
                'kondisi' => 'Baik, ada pergerakan janin',
                'status' => 'menetas',
                'tanggal_menetas' => now()->subDays(2)->toDateString(),
            ])->assertOk();

        $this->assertDatabaseHas('telurs', [
            'id' => $telur->id,
            'kondisi' => 'Baik, ada pergerakan janin',
            'status' => 'menetas',
        ]);
    }

    public function test_hapus_telur(): void
    {
        $burung = $this->burungUji();
        $telur = Telur::create([
            'burung_id' => $burung->id,
            'sesi' => 1,
            'jumlah' => 1,
            'tanggal_diambil' => now()->toDateString(),
            'diambil_oleh' => 'uji-pekerja-telur',
            'kondisi' => 'Baik',
        ]);

        $this->actingAs($this->admin)->deleteJson("/admin/telur/{$telur->id}")->assertOk();
        $this->assertDatabaseMissing('telurs', ['id' => $telur->id]);
    }
}
