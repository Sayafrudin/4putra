<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Kandang;
use App\Models\User;
use Tests\TestCase;

/**
 * Kandang & penempatan burung: papan drag-and-drop (Manajemen Farm).
 * Setiap burung adalah individu (tabel burungs), bukan stok katalog.
 */
class KandangTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-kandang-test@4putra.test'],
            ['name' => 'Admin Kandang', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        Burung::where('nama', 'like', 'uji-kandang%')->delete();
        Kandang::where('blok', 'Z')->delete();
        User::where('email', 'admin-kandang-test@4putra.test')->delete();
        parent::tearDown();
    }

    private function kandangUji(): Kandang
    {
        return Kandang::updateOrCreate(
            ['blok' => 'Z', 'nomor' => 1],
            ['tipe' => 'kecil']
        );
    }

    private function burungUji(array $attr = []): Burung
    {
        return Burung::create(array_merge([
            'nama' => 'uji-kandang-'.uniqid(),
            'spesies' => 'African Grey',
            'fase' => 'anakan',
        ], $attr));
    }

    public function test_papan_kandang_terproteksi_admin(): void
    {
        $this->get('/admin/kandang')->assertRedirect(route('login'));

        $bukanAdmin = User::updateOrCreate(
            ['email' => 'user-kandang-test@4putra.test'],
            ['name' => 'Bukan Admin', 'password' => bcrypt('password123'), 'role' => 'user']
        );

        $this->actingAs($bukanAdmin)->get('/admin/kandang')->assertStatus(403);
        $this->actingAs($this->admin)->get('/admin/kandang')->assertOk();

        $bukanAdmin->delete();
    }

    public function test_papan_render_blok_dan_isi_kandang(): void
    {
        $kandang = $this->kandangUji();
        $burung = $this->burungUji(['kandang_id' => $kandang->id]);
        $pool = $this->burungUji(); // belum ditempatkan

        $response = $this->actingAs($this->admin)->get('/admin/kandang');

        $response->assertOk();
        $response->assertSee('Z-1', false);
        $response->assertSee($burung->nama, false);
        $response->assertSee($pool->nama, false);

        $burung->delete();
        $pool->delete();
    }

    public function test_tambah_burung_divalidasi_dan_tersimpan(): void
    {
        $this->actingAs($this->admin)
            ->postJson('/admin/burung', ['spesies' => 'Sun Conure'])
            ->assertStatus(422);

        $this->actingAs($this->admin)
            ->postJson('/admin/burung', [
                'nama' => 'uji-kandang-tambah',
                'spesies' => 'Sun Conure',
                'fase' => 'anakan',
            ])->assertOk()
            ->assertJson(['burung' => ['nama' => 'uji-kandang-tambah']]);

        $this->assertDatabaseHas('burungs', ['nama' => 'uji-kandang-tambah']);
    }

    public function test_letakkan_dan_kosongkan_burung(): void
    {
        $kandang = $this->kandangUji();
        $burung = $this->burungUji();

        $this->actingAs($this->admin)
            ->post("/admin/burung/{$burung->id}/letakkan", ['kandang_id' => $kandang->id])
            ->assertOk();

        $this->assertDatabaseHas('burungs', ['id' => $burung->id, 'kandang_id' => $kandang->id]);

        $this->actingAs($this->admin)
            ->post("/admin/burung/{$burung->id}/kosongkan")
            ->assertOk();

        $this->assertDatabaseHas('burungs', ['id' => $burung->id, 'kandang_id' => null]);

        $burung->delete();
    }

    public function test_kapasitas_kandang_maksimal_dua_burung(): void
    {
        $kandang = $this->kandangUji();
        $a = $this->burungUji();
        $b = $this->burungUji();
        $c = $this->burungUji();

        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$a->id}/letakkan", ['kandang_id' => $kandang->id])->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$b->id}/letakkan", ['kandang_id' => $kandang->id])->assertOk();

        // Burung ke-3 ditolak dengan pesan jelas
        $res = $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$c->id}/letakkan", ['kandang_id' => $kandang->id]);
        $res->assertStatus(422);
        $res->assertJsonFragment(['ok' => false]);
        $this->assertStringContainsString('penuh', (string) $res->json('message'));

        $this->assertDatabaseHas('burungs', ['id' => $c->id, 'kandang_id' => null]);

        Burung::whereIn('id', [$a->id, $b->id, $c->id])->delete();
    }

    public function test_letakkan_ke_kandang_tidak_dikenal_ditolak(): void
    {
        $burung = $this->burungUji();

        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burung->id}/letakkan", ['kandang_id' => 999999999])
            ->assertStatus(422);

        $this->assertDatabaseHas('burungs', ['id' => $burung->id, 'kandang_id' => null]);

        $burung->delete();
    }

    public function test_update_data_burung_dengan_dna(): void
    {
        $burung = $this->burungUji();

        $this->actingAs($this->admin)
            ->putJson("/admin/burung/{$burung->id}", [
                'nama' => 'uji-kandang-ternama',
                'spesies' => 'African Grey',
                'fase' => 'dewasa',
                'gender' => 'betina',
                'nomor_dna' => 'DNA-UJI-001',
            ])->assertOk();

        $this->assertDatabaseHas('burungs', [
            'id' => $burung->id,
            'nama' => 'uji-kandang-ternama',
            'gender' => 'betina',
            'nomor_dna' => 'DNA-UJI-001',
        ]);
    }

    public function test_hapus_burung(): void
    {
        $burung = $this->burungUji();

        $this->actingAs($this->admin)->delete("/admin/burung/{$burung->id}")->assertOk();

        $this->assertDatabaseMissing('burungs', ['id' => $burung->id]);
    }
}
