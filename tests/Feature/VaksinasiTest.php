<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\User;
use App\Models\Vaksinasi;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Vaksinasi burung: CRUD + deteksi jatuh tempo (vaksin ulang tiap 1 bulan).
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
        // Role user boleh memantau vaksinasi (full akses menu monitoring).
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
                'tanggal_vaksin' => '2026-11-01',
            ])->assertOk();

        $this->assertDatabaseHas('vaksinasis', [
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'tanggal_vaksin' => '2026-11-01',
        ]);
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
                    'tanggal_vaksin' => '2026-11-18',
                ])->assertOk();

            $this->assertDatabaseHas('vaksinasis', ['burung_id' => $burung->id, 'tanggal_vaksin' => '2026-11-18']);
        } finally {
            Carbon::setTestNow();
            date_default_timezone_set($tzLama);
        }
    }

    public function test_burung_vaksin_lama_muncul_jatuh_tempo_dan_yang_baru_tidak(): void
    {
        $burungLama = Burung::create(['nama' => 'uji-vaksin-lamaA', 'spesies' => 'Macaw', 'fase' => 'dewasa']);
        $burungBaru = Burung::create(['nama' => 'uji-vaksin-baruB', 'spesies' => 'Macaw', 'fase' => 'dewasa']);

        // Vaksin 2 bulan lalu → jatuh tempo
        Vaksinasi::create([
            'burung_id' => $burungLama->id,
            'jenis' => 'uji-vaksin-lama',
            'tanggal_vaksin' => now()->subMonths(2)->toDateString(),
        ]);
        // Vaksin minggu lalu → tidak jatuh tempo
        Vaksinasi::create([
            'burung_id' => $burungBaru->id,
            'jenis' => 'uji-vaksin-baru',
            'tanggal_vaksin' => now()->subWeek()->toDateString(),
        ]);

        $res = $this->actingAs($this->admin)->get('/admin/vaksinasi');
        $res->assertOk();
        $res->assertSee('uji-vaksin-lamaA', false);   // masuk daftar jatuh tempo
        $res->assertSee('uji-vaksin-baruB', false);   // tetap tampil di riwayat

        DB::table('vaksinasis')->whereIn('jenis', ['uji-vaksin-lama', 'uji-vaksin-baru'])->delete();
    }

    public function test_hapus_vaksinasi(): void
    {
        $burung = $this->burungUji();
        $vaksin = Vaksinasi::create([
            'burung_id' => $burung->id,
            'jenis' => 'uji-vaksin-baru',
            'tanggal_vaksin' => '2026-11-01',
        ]);

        $this->actingAs($this->admin)->deleteJson("/admin/vaksinasi/{$vaksin->id}")->assertOk();
        $this->assertDatabaseMissing('vaksinasis', ['id' => $vaksin->id]);
    }
}
