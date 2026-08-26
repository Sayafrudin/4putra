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

    public function test_video_urls_persist_via_admin_store_and_clear_via_update(): void
    {
        // Bersihkan baris basi dari run uji yang gagal sebelumnya
        Facility::where('title', 'Uji Video Fasilitas')->delete();

        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin-uji-facility-video@4putra.test'],
            [
                'name' => 'Admin Uji Video Fasilitas',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $payload = [
            'title' => 'Uji Video Fasilitas',
            'title_en' => 'Video Facility Test',
            'category' => 'Umum',
            'category_en' => 'General',
            'description' => 'Deskripsi uji video fasilitas.',
            'cloudinary_urls' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ];

        $store = $this->actingAs($user)->postJson(route('admin.facilities.store'), $payload + [
            'video_urls' => ['https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
        ]);
        $store->assertStatus(200);

        $facility = Facility::where('title', 'Uji Video Fasilitas')->firstOrFail();
        $this->assertIsArray($facility->video_urls);
        $this->assertCount(1, $facility->video_urls);

        $this->actingAs($user)->get(route('admin.facilities.index'))
            ->assertStatus(200)
            ->assertSee('aqz-KE-bpKQ', false);

        $update = $this->actingAs($user)->putJson(
            route('admin.facilities.update', $facility->getKey()),
            $payload + ['video_urls' => []]
        );
        $update->assertStatus(200);
        $this->assertNull($facility->fresh()->video_urls);

        $update2 = $this->actingAs($user)->putJson(
            route('admin.facilities.update', $facility->getKey()),
            $payload + ['video_urls' => ['https://vimeo.com/76979871']]
        );
        $update2->assertStatus(200);
        $this->assertCount(1, $facility->fresh()->video_urls);

        $facility->delete();
        Cache::forget('admin.facilities');
        Cache::forget('public.facilities');
        $user->delete();
    }

    public function test_public_page_renders_multiple_video_embeds(): void
    {
        Facility::where('title', 'Uji Multi Video Fasilitas')->delete();

        $facility = Facility::create([
            'title' => 'Uji Multi Video Fasilitas',
            'title_en' => 'Multi Video Facility Test',
            'category' => 'Umum',
            'category_en' => 'General',
            'description' => 'Deskripsi multi video.',
            'video_urls' => [
                'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'https://vimeo.com/76979871',
            ],
            'images' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ]);
        Cache::forget('public.facilities');

        $this->get('/facilities')
            ->assertStatus(200)
            ->assertSee('aqz-KE-bpKQ', false)
            ->assertSee('76979871', false)
            ->assertDontSee('watch?v=aqz-KE-bpKQ', false)
            // Proteksi iframe: sandbox mematikan pop-out Drive, tanpa allow-popups
            ->assertSee('sandbox="allow-scripts allow-same-origin"', false)
            ->assertDontSee('allow-popups', false);

        $facility->delete();
        Cache::forget('public.facilities');
    }
}
