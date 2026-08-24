<?php

namespace Tests\Feature;

use App\Models\Facility;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class FacilityTest extends TestCase
{
    public function test_public_page_loads_and_renders_facility_grid(): void
    {
        $facility = Facility::create([
            'title' => 'Uji Coba Grid Fasilitas',
            'title_en' => 'Facility Grid Test',
            'category' => 'Umum',
            'category_en' => 'General',
            'description' => 'Deskripsi uji cobe fasilitas.',
            'images' => ['https://res.cloudinary.com/kjcs8wz3/image/upload/v1755000000/4putra/collections/test.jpg'],
        ]);
        Cache::forget('public.facilities');

        $response = $this->get('/facilities');

        // Locale default environment test = EN, jadi judul EN yang tampil
        $response->assertStatus(200);
        $response->assertSee('Facility Grid Test');
        $response->assertSee('res.cloudinary.com', false);

        $facility->delete();
        Cache::forget('public.facilities');
    }

    public function test_admin_index_requires_authentication(): void
    {
        $this->get(route('admin.facilities.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_index_renders_monitoring_table_for_authenticated_user(): void
    {
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin-uji-facility@4putra.test'],
            [
                'name' => 'Admin Uji',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($user)->get(route('admin.facilities.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Data Fasilitas');
        $response->assertSee('+ Tambah Fasilitas');
        $response->assertSee('Tindakan Kontrol');

        $user->delete();
    }

    public function test_images_are_decoded_as_array_from_json_column(): void
    {
        $facility = Facility::create([
            'title' => 'Uji Cast Array Fasilitas',
            'category' => 'Medis',
            'description' => 'Validasi cast images array.',
            'images' => [
                'https://res.cloudinary.com/kjcs8wz3/image/upload/v1755000000/4putra/collections/a.jpg',
                'https://res.cloudinary.com/kjcs8wz3/image/upload/v1755000000/4putra/collections/b.jpg',
            ],
        ]);

        $fresh = Facility::findOrFail($facility->getKey());

        $this->assertIsArray($fresh->images);
        $this->assertCount(2, $fresh->images);

        $facility->delete();
    }
}
