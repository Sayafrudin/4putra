<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Login dashboard memakai username (bukan email).
 */
class UsernameLoginTest extends TestCase
{
    protected function tearDown(): void
    {
        User::where('email', 'uname-test@4putra.test')->delete();

        parent::tearDown();
    }

    public function test_login_pakai_username_dan_password(): void
    {
        User::updateOrCreate(['email' => 'uname-test@4putra.test'], [
            'name' => 'Uji Username',
            'username' => 'uname-test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->post('/login', ['username' => 'uname-test', 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));
        $this->assertAuthenticated();
    }

    public function test_login_pakai_email_ditolak(): void
    {
        User::updateOrCreate(['email' => 'uname-test@4putra.test'], [
            'name' => 'Uji Username',
            'username' => 'uname-test',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->from('/login')->post('/login', ['username' => 'uname-test@4putra.test', 'password' => 'password123'])
            ->assertRedirect('/login')
            ->assertSessionHasErrors('username');

        $this->assertGuest();

        User::where('email', 'uname-test@4putra.test')->delete();
    }
}
