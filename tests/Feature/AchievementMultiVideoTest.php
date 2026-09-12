<?php

namespace Tests\Feature;

use App\Models\Achievement;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AchievementMultiVideoTest extends TestCase
{
    public function test_video_files_persist_via_admin_store_and_update(): void
    {
        // Bersihkan baris basi dari run uji yang gagal sebelumnya
        Achievement::where('title', 'Uji Multi Video')->delete();

        $user = \App\Models\User::updateOrCreate(
            ['email' => 'admin-uji-ach-video@4putra.test'],
            [
                'name' => 'Admin Uji Video',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        $payload = [
            'title' => 'Uji Multi Video',
            'year' => 2026,
            'date' => '2026-09-12',
            'description' => 'Deskripsi uji multi video.',
            'cloudinary_urls' => [
                'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'https://res.cloudinary.com/demo/image/upload/kitten.jpg',
            ],
            'cloudinary_types' => ['image', 'image'],
        ];

        // Simulasi JS: video file upload masuk video_urls[], gambar di cloudinary_urls[]
        $store = $this->actingAs($user)->postJson(route('admin.achievements.store'), $payload + [
            'video_urls' => [
                'https://res.cloudinary.com/demo/video/upload/v1/4putra/achievements/a.mp4',
                'https://res.cloudinary.com/demo/video/upload/v1/4putra/achievements/b.mp4',
            ],
        ]);
        $store->assertStatus(200);

        $achievement = Achievement::where('title', 'Uji Multi Video')->firstOrFail();
        // 10 video upload harus tersimpan semua, bukan tertimpa video terakhir
        $this->assertIsArray($achievement->video_urls);
        $this->assertCount(2, $achievement->video_urls);
        $this->assertSame(
            'https://res.cloudinary.com/demo/video/upload/v1/4putra/achievements/a.mp4',
            $achievement->video_urls[0]
        );

        // Link video (video_url) tetap bekerja dan tergabung
        $storeLink = $this->actingAs($user)->putJson(
            route('admin.achievements.update', $achievement->getKey()),
            $payload + [
                'video_url' => ['https://www.youtube.com/watch?v=aqz-KE-bpKQ'],
                'keep_video_urls' => [$achievement->video_urls[0]], // b.mp4 dihapus admin
                'video_urls' => [],
            ]
        );
        $storeLink->assertStatus(200);
        $fresh = $achievement->fresh();
        $this->assertCount(1, $fresh->video_urls);
        $this->assertSame($achievement->video_urls[0], $fresh->video_urls[0]);
        $this->assertSame(['https://www.youtube.com/watch?v=aqz-KE-bpKQ'], json_decode($fresh->video_url, true));

        // Semua video dihapus -> kolom null
        $clear = $this->actingAs($user)->putJson(
            route('admin.achievements.update', $achievement->getKey()),
            $payload + ['video_url' => [], 'keep_video_urls' => []]
        );
        $clear->assertStatus(200);
        $this->assertNull($achievement->fresh()->video_urls);

        $achievement->delete();
        Cache::forget('admin.achievements');
        Cache::forget('public.achievements');
        $user->delete();
    }

    public function test_public_page_renders_multiple_video_files(): void
    {
        Achievement::where('title', 'Uji Video File Publik')->delete();

        $achievement = Achievement::create([
            'title' => 'Uji Video File Publik',
            'year' => 2026,
            'date' => '2026-09-12',
            'description' => 'Deskripsi uji video file publik.',
            'video_urls' => [
                'https://res.cloudinary.com/demo/video/upload/v1/4putra/achievements/aaa.mp4',
                'https://res.cloudinary.com/demo/video/upload/v1/4putra/achievements/bbb.webm',
            ],
            'images' => ['https://res.cloudinary.com/demo/image/upload/sample.jpg'],
        ]);
        Cache::forget('public.achievements');

        $this->get('/achievements')
            ->assertStatus(200)
            ->assertSee('vfile-0', false)
            ->assertSee('vfile-1', false)
            ->assertSee('aaa.mp4', false)
            ->assertSee('bbb.webm', false)
            ->assertSee('video/webm', false);

        $achievement->delete();
        Cache::forget('public.achievements');
    }
}