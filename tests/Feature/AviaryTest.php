<?php

namespace Tests\Feature;

use App\Models\Burung;
use App\Models\Kandang;
use App\Models\User;
use Tests\TestCase;

/**
 * Aviary (kandang besar pra-breeding): 2 kandang tipe 'aviary' tanpa batas isi,
 * terpisah dari papan kandang biasa, dan lokasinya terlihat lintas menu burung.
 */
class AviaryTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-aviary-test@4putra.test'],
            ['name' => 'Admin Aviary', 'username' => 'admin-aviary-test', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        Burung::where('nama', 'like', 'uji-aviary%')->delete();
        Kandang::where('blok', 'Z')->delete();
        User::where('email', 'admin-aviary-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_migrasi_menyiapkan_dua_aviary_berlabel_jelas(): void
    {
        $aviaries = Kandang::where('tipe', 'aviary')->orderBy('nomor')->get();

        $this->assertCount(2, $aviaries);
        $this->assertSame('Aviary 1', $aviaries[0]->label);
        $this->assertSame('Aviary 2', $aviaries[1]->label);
    }

    public function test_papan_kandang_mengecualikan_aviary_dan_pool_menandai_burung_aviary(): void
    {
        $aviary = Kandang::where('tipe', 'aviary')->orderBy('nomor')->first();
        Burung::create([
            'nama' => 'uji-aviary-a', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'kandang_id' => $aviary->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/kandang');
        $response->assertOk();

        // Papan kandang tidak menampilkan kartu aviary
        $this->assertStringNotContainsString('data-kandang-label="Aviary 1"', $response->getContent());
        // Burung aviary muncul di pool dengan badge lokasi (bisa langsung dipindah ke kandang)
        $response->assertSee('uji-aviary-a');
        $response->assertSee('Di Aviary 1');
    }

    public function test_halaman_aviary_pool_hanya_burung_bebas(): void
    {
        $reguler = Kandang::where('tipe', '!=', 'aviary')->orderBy('blok')->orderBy('nomor')->first();
        Burung::create(['nama' => 'uji-aviary-b', 'spesies' => 'African Grey', 'fase' => 'dewasa']);
        Burung::create([
            'nama' => 'uji-aviary-c', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'kandang_id' => $reguler->id,
        ]);

        $response = $this->actingAs($this->admin)->get('/admin/aviary');
        $response->assertOk();

        // Dua kartu aviary dirender
        $response->assertSee('Aviary 1');
        $response->assertSee('Aviary 2');

        // Pool aviary: hanya burung bebas — burung kandang breeding tidak
        // ditawarkan (aturan bos: breeding → aviary harus ditolak).
        $response->assertSee('uji-aviary-b');
        $response->assertDontSee('uji-aviary-c');
        $response->assertDontSee('Di Kandang '.$reguler->label);
    }

    public function test_burung_kandang_breeding_ditolak_masuk_aviary(): void
    {
        $aviary = Kandang::where('tipe', 'aviary')->orderBy('nomor')->first();
        $reguler = Kandang::updateOrCreate(
            ['blok' => 'Z', 'nomor' => 2],
            ['tipe' => 'kecil']
        );
        $burung = Burung::create([
            'nama' => 'uji-aviary-d', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'kandang_id' => $reguler->id,
        ]);

        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burung->id}/letakkan", ['kandang_id' => $aviary->id])
            ->assertStatus(422)
            ->assertJsonPath('ok', false);

        // Posisi burung tidak berubah: tetap di kandang breeding
        $this->assertEquals($reguler->id, $burung->fresh()->kandang_id);
    }

    public function test_burung_aviary_tetap_bisa_masuk_kandang_breeding(): void
    {
        $aviary = Kandang::where('tipe', 'aviary')->orderBy('nomor')->first();
        $reguler = Kandang::updateOrCreate(
            ['blok' => 'Z', 'nomor' => 3],
            ['tipe' => 'kecil']
        );
        $burung = Burung::create([
            'nama' => 'uji-aviary-e', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'kandang_id' => $aviary->id,
        ]);

        // Arah sah: promosi aviary → kandang breeding
        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burung->id}/letakkan", ['kandang_id' => $reguler->id])
            ->assertOk();

        $this->assertEquals($reguler->id, $burung->fresh()->kandang_id);
    }

    public function test_letakkan_ke_aviary_tanpa_batas_kapasitas(): void
    {
        $aviary = Kandang::where('tipe', 'aviary')->orderBy('nomor')->first();
        $burungs = collect(range(1, 3))->map(fn ($i) => Burung::create([
            'nama' => 'uji-aviary-ke'.$i, 'spesies' => 'African Grey', 'fase' => 'dewasa',
        ]));

        foreach ($burungs as $burung) {
            $this->actingAs($this->admin)
                ->postJson("/admin/burung/{$burung->id}/letakkan", ['kandang_id' => $aviary->id])
                ->assertOk();
        }

        $this->assertSame(3, $aviary->fresh()->burungs()->count());

        $burungs->each(fn ($b) => $b->delete());
    }

    public function test_letakkan_ke_kandang_biasa_tetap_dibatasi_dua(): void
    {
        // Kandang uji terpisah agar tidak tergantung isi kandang riil di DB dev
        $reguler = Kandang::updateOrCreate(
            ['blok' => 'Z', 'nomor' => 1],
            ['tipe' => 'kecil']
        );
        $burungs = collect(range(1, 3))->map(fn ($i) => Burung::create([
            'nama' => 'uji-aviary-r'.$i, 'spesies' => 'African Grey', 'fase' => 'dewasa',
        ]));

        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burungs[0]->id}/letakkan", ['kandang_id' => $reguler->id])->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burungs[1]->id}/letakkan", ['kandang_id' => $reguler->id])->assertOk();
        $this->actingAs($this->admin)
            ->postJson("/admin/burung/{$burungs[2]->id}/letakkan", ['kandang_id' => $reguler->id])->assertStatus(422);

        $burungs->each(fn ($b) => $b->delete());
    }

    public function test_pasangan_telur_mengabaikan_aviary(): void
    {
        $aviary = Kandang::where('tipe', 'aviary')->orderBy('nomor')->first();
        Burung::create([
            'nama' => 'uji-aviary-j', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'gender' => 'jantan', 'kandang_id' => $aviary->id,
        ]);
        Burung::create([
            'nama' => 'uji-aviary-b2', 'spesies' => 'African Grey', 'fase' => 'dewasa', 'gender' => 'betina', 'kandang_id' => $aviary->id,
        ]);

        // Dua burung dewasa di aviary BUKAN kandidat pasangan telur (aviary = pra-breeding).
        // 'Kandang Aviary N' tidak boleh muncul di dropdown pasangan.
        $response = $this->actingAs($this->admin)->get('/admin/telur');
        $response->assertOk();
        $this->assertStringNotContainsString('Kandang Aviary', $response->getContent());
    }

    public function test_role_user_bisa_mengakses_aviary(): void
    {
        $roleUser = User::updateOrCreate(
            ['email' => 'user-aviary-test@4putra.test'],
            ['name' => 'Role User Aviary', 'username' => 'user-aviary-test', 'password' => bcrypt('password123'), 'role' => 'user']
        );

        $this->actingAs($roleUser)->get('/admin/aviary')->assertOk();

        $roleUser->delete();
    }
}
