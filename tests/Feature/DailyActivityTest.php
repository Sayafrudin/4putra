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
}
