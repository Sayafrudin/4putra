<?php

namespace Tests\Feature;

use App\Models\Leadership;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class AboutManagementTest extends TestCase
{
    private function adminUser(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin-uji-about@4putra.test'],
            [
                'name' => 'Admin Uji About',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );
    }

    public function test_public_about_renders_leadership_from_db(): void
    {
        $leader = Leadership::create([
            'name' => 'Uji Leadership',
            'role' => 'Penguji',
            'role_en' => 'Tester',
            'photo_path' => 'img/manager.png',
            'sort_order' => 99,
        ]);
        Cache::forget('about.leaderships');

        $this->get('/about')
            ->assertStatus(200)
            ->assertSee('Uji Leadership')
            ->assertSee('Tester');

        $leader->delete();
        Cache::forget('about.leaderships');
    }

    public function test_admin_page_renders_about_section(): void
    {
        $response = $this->actingAs($this->adminUser())
            ->get(route('admin.achievements.index'));

        $response->assertStatus(200);
        $response->assertSee('Manajemen About Us');
        $response->assertSee('Media Hero');
        $response->assertSee('Leadership');
        $response->assertSee('Tambah Management');

        $this->adminUser()->delete();
    }

    public function test_leader_crud_end_to_end(): void
    {
        $admin = $this->adminUser();

        // Create
        $this->actingAs($admin)
            ->postJson(route('admin.about.leadership.store'), [
                'name' => 'Uji CRUD Leadership',
                'role' => 'Role Uji',
                'role_en' => 'Test Role',
                'photo_path' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'sort_order' => 99,
            ])->assertJson(['success' => true]);

        $leader = Leadership::where('name', 'Uji CRUD Leadership')->first();
        $this->assertNotNull($leader);
        $this->assertSame('Role Uji', $leader->role);

        // Muncul di publik
        $this->get('/about')->assertSee('Uji CRUD Leadership');

        // Update
        $this->actingAs($admin)
            ->putJson(route('admin.about.leadership.update', $leader->id), [
                'name' => 'Uji CRUD Leadership Revisi',
                'role' => 'Role Uji Revisi',
                'role_en' => 'Revised Test Role',
                'photo_path' => 'https://res.cloudinary.com/demo/image/upload/sample.jpg',
                'sort_order' => 100,
            ])->assertJson(['success' => true]);
        $this->assertSame('Role Uji Revisi', $leader->fresh()->role);

        // Delete
        $this->actingAs($admin)
            ->deleteJson(route('admin.about.leadership.destroy', $leader->id))
            ->assertJson(['success' => true]);
        $this->assertNull(Leadership::find($leader->id));

        Cache::forget('about.leaderships');
        $admin->delete();
    }

    public function test_media_update_end_to_end(): void
    {
        $admin = $this->adminUser();
        $before = \App\Models\AboutPage::current();

        $this->actingAs($admin)
            ->postJson(route('admin.about.media.update'), [
                'media_type' => 'video',
                'media_path' => 'https://res.cloudinary.com/demo/video/upload/sample.mp4',
            ])->assertJson(['success' => true]);

        $this->assertSame('video', \App\Models\AboutPage::current()->media_type);
        $this->get('/about')->assertSee('<video', false);

        // Kembalikan ke kondisi semula
        $this->actingAs($admin)
            ->postJson(route('admin.about.media.update'), [
                'media_type' => $before->media_type,
                'media_path' => $before->media_path,
            ])->assertJson(['success' => true]);

        $admin->delete();
    }
}
