<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Session;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

class AdminIntendedRedirectTest extends TestCase
{
    public function test_guest_hitting_admin_page_stores_intended_url(): void
    {
        $this->get(route('admin.facilities.index'));

        $this->assertStringContainsString('/admin/facilities', (string) session('url.intended'));
    }

    public function test_email_login_redirects_to_intended_url(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin-intended@4putra.test'],
            [
                'name' => 'Admin Intended',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );

        Session::put('url.intended', url('/admin/facilities'));

        $response = $this->post('/login', [
            'email' => 'admin-intended@4putra.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(url('/admin/facilities'));

        $user->delete();
    }

    public function test_google_callback_redirects_to_intended_url(): void
    {
        $user = User::updateOrCreate(
            ['email' => 'admin-intended-google@4putra.test'],
            [
                'name' => 'Admin Google',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'google_id' => 'g-intended-123',
            ]
        );

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-intended-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('admin-intended-google@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Admin Google');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        Session::put('url.intended', url('/admin/facilities'));

        $response = $this->get('/auth/google/callback');

        $response->assertRedirect(url('/admin/facilities'));

        $user->delete();
    }
}
