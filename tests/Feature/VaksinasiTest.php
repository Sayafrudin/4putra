<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Kandang;
use App\Models\User;
use App\Models\Vaksinasi;
use App\Support\DashboardStats;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Obat & Vaksinasi burung: CRUD + deteksi jatuh tempo per metode
 * (suntik = 1x/tahun, oral = tiap 3 bulan) + catat massal per kandang.
 */
class VaksinasiTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-vaksin-test@4putra.test'],
            ['name' => 'Admin Vaksin', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        DB::table('vaksinasis')->whereIn('jenis', ['uji-vaksin-lama', 'uji-vaksin-baru'])->delete();
        Burung::where('nama', 'like', 'uji-vaksin%')->delete();
        Kandang::where('blok', 'Z')->where('nomor', '>=', 900)->delete();
        User::where('email', 'admin-vaksin-test@4putra.test')->delete();
        User::where('email', 'user-vaksin-test@4putra.test')->delete();
        parent::tearDown();
    }

    private function burungUji(): Burung
    {
        return Burung::create(['nama' => 'uji-vaksin-burung', 'spesies' => 'African Grey', 'fase' => 'dewasa']);
    }

    public function test_menu_terbuka_role_user_dan_terproteksi_login(): void
    {
        $this->get('/admin/vaksinasi')->assertRedirect(route('login'));

        $roleUser = User::updateOrCreate(
            ['email' => 'user-vaksin-test@4putra.test'],
            ['name' => 'Role User', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        // Role user boleh memantau obat & vaksinasi (akses menu monitoring).
        $this->actingAs($roleUser)->get('/admin/vaksinasi')->assertOk();
        $this->actingAs($this->admin)->get('/admin/vaksinasi')->assertOk();
    }

    public function test_tambah_vaksinasi_divalidasi_dan_tersimpan(): void
    {
        $burung = $this->burungUji();

        // Tanpa jenis & burung_id tidak valid → 422
        $this->actingAs($this->admin)
            ->postJson('/admin/vaksinasi', ['tanggal_vaksin' => '2026-11-01'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/vaksinasi', [
                'burung_id' => $burung->id,
                'jenis' => 'uji-vaksin-baru',
                'metode' => 'suntik',
                'dosis' => [(string) $burung->id => '0.5 ml'],
                'tanggal_vaksin' => '2026-11-01',
            ])->assertOk();

        $this->assertDatabaseHas('vaksinasis', [
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'metode' => 'suntik',
            'dosis' => '0.5 ml',
            'tanggal_vaksin' => '2026-11-01',
        ]);
    }

    public function test_metode_wajib_valid(): void
    {
        $burung = $this->burungUji();

        $this->actingAs($this->admin)
            ->postJson('/admin/vaksinasi', [
                'burung_id' => $burung->id,
                'jenis' => 'uji-vaksin-baru',
                'metode' => 'tablet', // bukan suntik/oral
                'tanggal_vaksin' => '2026-11-01',
            ])->assertStatus(422);
    }

    public function test_tanggal_hari_ini_diterima_pada_dini_hari_wib(): void
    {
        // Simulasi produksi (UTC): 18 Nov 01:00 WIB = 17 Nov 18:00 UTC.
        Carbon::setTestNow(Carbon::parse('2026-11-17 18:00', 'UTC'));
        $tzLama = date_default_timezone_get();
        date_default_timezone_set('UTC');

        try {
            $burung = $this->burungUji();
            $this->actingAs($this->admin)
                ->postJson('/admin/vaksinasi', [
                    'burung_id' => $burung->id,
                    'jenis' => 'uji-vaksin-baru',
                    'metode' => 'oral',
                    'tanggal_vaksin' => '2026-11-18',
                ])->assertOk();

            $this->assertDatabaseHas('vaksinasis', ['burung_id' => $burung->id, 'tanggal_vaksin' => '2026-11-18']);
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($tzLama);
        }
    }

    public function test_jatuh_tempo_mengikuti_siklus_metode(): void
    {
        $oralLama = Burung::create(['nama' => 'uji-vaksin-lamaA', 'spesies' => 'Macaw', 'fase' => 'dewasa']);
        $suntikBaru = Burung::create(['nama' => 'uji-vaksin-baruB', 'spesies' => 'Macaw', 'fase' => 'dewasa']);

        // Oral 4 bulan lalu → jatuh tempo (siklus 3 bulan)
        Vaksinasi::create([
            'burung_id' => $oralLama->id,
            'jenis' => 'uji-vaksin-lama',
            'metode' => 'oral',
            'tanggal_vaksin' => now()->subMonths(4)->toDateString(),
        ]);
        // Suntik 2 bulan lalu → masih terlindungi (siklus 12 bulan)
        Vaksinasi::create([
            'burung_id' => $suntikBaru->id,
            'jenis' => 'uji-vaksin-baru',
            'metode' => 'suntik',
            'tanggal_vaksin' => now()->subMonths(2)->toDateString(),
        ]);

        $res = $this->actingAs($this->admin)->get('/admin/vaksinasi');
        $res->assertOk();
        $res->assertSee('uji-vaksin-lamaA', false);   // jatuh tempo (panel + badge merah)
        $res->assertSee('uji-vaksin-baruB', false);   // masih terlindungi

        $this->assertTrue(Vaksinasi::where('burung_id', $oralLama->id)->first()->sudah_jatuh_tempo);
        $this->assertFalse(Vaksinasi::where('burung_id', $suntikBaru->id)->first()->sudah_jatuh_tempo);
    }

    public function test_catat_massal_untuk_beberapa_burung(): void
    {
        $b1 = Burung::create(['nama' => 'uji-vaksin-massal1', 'spesies' => 'Macaw', 'fase' => 'dewasa']);
        $b2 = Burung::create(['nama' => 'uji-vaksin-massal2', 'spesies' => 'Macaw', 'fase' => 'dewasa']);

        $this->actingAs($this->admin)
            ->postJson('/admin/vaksinasi', [
                'burung_ids' => [$b1->id, $b2->id],
                'jenis' => 'Obat Cacing',
                'metode' => 'oral',
                'dosis' => [(string) $b1->id => '1 tablet', (string) $b2->id => '2 tablet'],
                'tanggal_vaksin' => '2026-11-01',
            ])->assertOk();

        $this->assertSame(2, DB::table('vaksinasis')->where('jenis', 'Obat Cacing')->count());
        $this->assertSame('1 tablet', DB::table('vaksinasis')->where('burung_id', $b1->id)->where('jenis', 'Obat Cacing')->value('dosis'));
        $this->assertSame('2 tablet', DB::table('vaksinasis')->where('burung_id', $b2->id)->where('jenis', 'Obat Cacing')->value('dosis'));
    }

    public function test_hapus_vaksinasi(): void
    {
        $burung = $this->burungUji();
        $vaksin = Vaksinasi::create([
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'metode' => 'suntik',
            'tanggal_vaksin' => '2026-11-01',
        ]);

        $this->actingAs($this->admin)->deleteJson("/admin/vaksinasi/{$vaksin->id}")->assertOk();
        $this->assertDatabaseMissing('vaksinasis', ['id' => $vaksin->id]);
    }

    public function test_detail_kandang_riwayat_dan_export_pdf(): void
    {
        $kandang = Kandang::create(['blok' => 'Z', 'nomor' => 900, 'tipe' => 'kecil']);
        $burung = Burung::create(['nama' => 'uji-vaksin-detail', 'spesies' => 'Macaw', 'fase' => 'dewasa', 'kandang_id' => $kandang->id]);
        Vaksinasi::create([
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'metode' => 'suntik',
            'tanggal_vaksin' => '2026-11-01',
        ]);
        $burungLain = Burung::create(['nama' => 'uji-vaksin-detail2', 'spesies' => 'Macaw', 'fase' => 'dewasa']); // kandang lain (tanpa kandang)

        $this->actingAs($this->admin)->get("/admin/vaksinasi/{$kandang->id}")
            ->assertOk()
            ->assertSee('uji-vaksin-detail', false)
            ->assertDontSee('uji-vaksin-detail2', false);

        $this->actingAs($this->admin)->get("/admin/vaksinasi/{$kandang->id}/export/pdf")->assertOk();
    }

    public function test_dashboard_stats_memuat_terlindung_dan_pernah_dicatat(): void
    {
        Cache::forget('dashboard.stats');
        $burung = $this->burungUji();
        Vaksinasi::create([
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'metode' => 'suntik',
            'tanggal_vaksin' => now('Asia/Jakarta')->toDateString(),
        ]);

        $stats = DashboardStats::untuk($this->admin);
        $this->assertGreaterThanOrEqual(1, $stats['vaksinLindung']);
        $this->assertGreaterThanOrEqual(1, $stats['vaksinPernah']);
        Cache::forget('dashboard.stats');
    }
}
