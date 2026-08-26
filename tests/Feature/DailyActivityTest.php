<?php

namespace Tests\Feature;

use App\Models\DailyActivity;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class DailyActivityTest extends TestCase
{
    public function test_public_page_loads_and_renders_activity_feed(): void
    {
        $activity = DailyActivity::create([
            'title' => 'Uji Coba Feed Aktivitas',
            'description' => 'Deskripsi uji cobe dokumentasi aktivitas harian.',
            'activity_date' => '2026-08-20',
            'images' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ]);
        Cache::forget('public.daily_activities');

        $response = $this->get('/daily-activities');

        $response->assertStatus(200);
        $response->assertSee('Uji Coba Feed Aktivitas');
        $response->assertSee('res.cloudinary.com', false);

        $activity->delete();
        Cache::forget('public.daily_activities');
    }

    public function test_admin_index_requires_authentication(): void
    {
        $this->get(route('admin.daily-activities.index'))
            ->assertRedirect(route('login'));
    }

    public function test_admin_index_renders_monitoring_table_for_authenticated_user(): void
    {
        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin-uji-daily@4putra.test'],
            [
                'name' => 'Admin Uji',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $response = $this->actingAs($user)->get(route('admin.daily-activities.index'));

        $response->assertStatus(200);
        $response->assertSee('Monitoring Data Aktivitas Harian');
        $response->assertSee('+ Tambah Aktivitas');
        $response->assertSee('Tindakan Kontrol');

        $user->delete();
    }

    public function test_images_are_decoded_as_array_from_json_column(): void
    {
        $activity = DailyActivity::create([
            'title' => 'Uji Cast Array',
            'description' => 'Validasi cast images array.',
            'activity_date' => '2026-08-21',
            'images' => [
                'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'https://res.cloudinary.com/demo/image/upload/kitten.jpg',
            ],
        ]);

        $fresh = DailyActivity::findOrFail($activity->getKey());

        $this->assertIsArray($fresh->images);
        $this->assertCount(2, $fresh->images);

        $activity->delete();
    }

    public function test_video_urls_persist_via_admin_store_and_clear_via_update(): void
    {
        // Bersihkan baris basi dari run uji yang gagal sebelumnya
        DailyActivity::where('title', 'Uji Video Aktivitas')->delete();

        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin-uji-daily-video@4putra.test'],
            [
                'name' => 'Admin Uji Video',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $payload = [
            'title' => 'Uji Video Aktivitas',
            'description' => 'Deskripsi uji video.',
            'activity_date' => '2026-08-27',
            'cloudinary_urls' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ];

        $store = $this->actingAs($user)->postJson(route('admin.daily-activities.store'), $payload + [
            'video_urls' => [
                'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'https://vimeo.com/76979871',
            ],
        ]);
        $store->assertStatus(200);

        $activity = DailyActivity::where('title', 'Uji Video Aktivitas')->firstOrFail();
        $this->assertIsArray($activity->video_urls);
        $this->assertCount(2, $activity->video_urls);
        $this->assertSame('https://www.youtube.com/watch?v=aqz-KE-bpKQ', $activity->video_urls[0]);

        // Data video_urls wajib terkirim ke JSON modal edit di halaman admin
        $this->actingAs($user)->get(route('admin.daily-activities.index'))
            ->assertStatus(200)
            ->assertSee('aqz-KE-bpKQ', false)
            ->assertSee('76979871', false);

        $update = $this->actingAs($user)->putJson(
            route('admin.daily-activities.update', $activity->getKey()),
            $payload + ['video_urls' => []]
        );
        $update->assertStatus(200);
        $this->assertNull($activity->fresh()->video_urls);

        $update2 = $this->actingAs($user)->putJson(
            route('admin.daily-activities.update', $activity->getKey()),
            $payload + ['video_urls' => ['https://vimeo.com/76979871']]
        );
        $update2->assertStatus(200);
        $this->assertCount(1, $activity->fresh()->video_urls);

        $activity->delete();
        Cache::forget('admin.daily_activities');
        Cache::forget('public.daily_activities');
        $user->delete();
    }

    public function test_public_page_renders_multiple_video_embeds(): void
    {
        DailyActivity::where('title', 'Uji Embed Video Publik')->delete();

        $activity = DailyActivity::create([
            'title' => 'Uji Embed Video Publik',
            'description' => 'Deskripsi embed video.',
            'activity_date' => '2026-08-27',
            'video_urls' => [
                'https://www.youtube.com/watch?v=aqz-KE-bpKQ',
                'https://vimeo.com/76979871',
            ],
            'images' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ]);
        Cache::forget('public.daily_activities');

        // ID kedua video terkirim ke feed frontend, dan bentuk "watch?v=" tidak
        // (yang diserialisasi ke JS hanyalah bentuk embed hasil konversi)
        $this->get('/daily-activities')
            ->assertStatus(200)
            ->assertSee('aqz-KE-bpKQ', false)
            ->assertSee('76979871', false)
            ->assertDontSee('watch?v=aqz-KE-bpKQ', false)
            // Proteksi iframe: sandbox mematikan pop-out Drive, tanpa allow-popups
            ->assertSee('sandbox="allow-scripts allow-same-origin"', false)
            ->assertDontSee('allow-popups', false);

        $activity->delete();
        Cache::forget('public.daily_activities');
    }
}
