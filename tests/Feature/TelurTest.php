<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Telur;
use App\Models\User;
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
        User::where('email', 'admin-telur-test@4putra.test')->delete();
        User::where('email', 'user-telur-test@4putra.test')->delete();
        parent::tearDown();
    }

    private function burungUji(): Burung
    {
        return Burung::create(['nama' => 'uji-telur-camelot', 'spesies' => 'Camelot Macaw', 'fase' => 'dewasa']);
    }

    public function test_hanya_admin_yang_bisa_mengakses(): void
    {
        $this->get('/admin/telur')->assertRedirect(route('login'));

        $bukanAdmin = User::updateOrCreate(
            ['email' => 'user-telur-test@4putra.test'],
            ['name' => 'Bukan Admin', 'password' => bcrypt('password123'), 'role' => 'user']
        );
        $this->actingAs($bukanAdmin)->get('/admin/telur')->assertStatus(403);
        $this->actingAs($this->admin)->get('/admin/telur')->assertOk();
    }

    public function test_tambah_telur_divalidasi_dan_tersimpan(): void
    {
        $burung = $this->burungUji();

        $this->actingAs($this->admin)
            ->postJson('/admin/telur', ['diambil_oleh' => 'uji-pekerja-telur'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/telur', [
                'burung_id' => $burung->id,
                'sesi' => 1,
                'jumlah' => 2,
                'tanggal_diambil' => now()->subDays(15)->toDateString(),
                'diambil_oleh' => 'uji-pekerja-telur',
                'kondisi' => 'Kondisi telur baik',
            ])->assertOk();

        $this->assertDatabaseHas('telurs', [
            'burung_id' => $burung->id,
            'sesi' => 1,
            'jumlah' => 2,
            'status' => 'diinkubasi',
        ]);
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
