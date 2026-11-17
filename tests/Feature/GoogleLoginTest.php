<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Login Google = whitelist tabel users: hanya email yang SUDAH terdaftar
 * (dibuat admin via Kelola Akun User, password opsional) yang boleh masuk.
 * Google TIDAK pernah membuat akun baru — Gmail asing ditolak di /login.
 * Akun Google-link dilarang login username/password.
 */
class GoogleLoginTest extends TestCase
{
    private array $emails = [
        'gl-admin@4putra.test',
        'gl-user@4putra.test',
        'gl-asing@gmail.com',
    ];

    protected function tearDown(): void
    {
        User::whereIn('email', $this->emails)->delete();

        parent::tearDown();
    }

    private function mockGoogle(string $email, string $id = 'g-gl-1'): void
    {
        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn($id);
        $abstractUser->shouldReceive('getEmail')->andReturn($email);
        $abstractUser->shouldReceive('getName')->andReturn('Nama Google');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);
    }

    public function test_gmail_tak_dikenal_ditolak_dan_tidak_membuat_akun(): void
    {
        Log::spy();
        $this->mockGoogle('gl-asing@gmail.com');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'gl-asing@gmail.com']);
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
            return str_contains($message, 'tidak terdaftar') && $context['email'] === 'gl-asing@gmail.com';
        });
    }

    public function test_email_terdaftar_user_biasa_bisa_login_dan_google_id_terlink(): void
    {
        $user = User::updateOrCreate(['email' => 'gl-user@4putra.test'], [
            'name' => 'User Google',
            'username' => 'gl-user',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);
        $this->mockGoogle('gl-user@4putra.test');

        $this->get('/auth/google/callback')->assertRedirect(url('/'));

        $this->assertAuthenticatedAs($user->fresh());
        $this->assertNotEmpty($user->fresh()->google_id, 'google_id wajib ter-link setelah login Google');
    }

    public function test_email_terdaftar_admin_masuk_dashboard(): void
    {
        $user = User::updateOrCreate(['email' => 'gl-admin@4putra.test'], [
            'name' => 'Admin Google',
            'username' => 'gl-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);
        $this->mockGoogle('gl-admin@4putra.test');

        $this->get('/auth/google/callback')->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($user->fresh());
    }

    public function test_akun_google_link_dilarang_login_username_password(): void
    {
        User::updateOrCreate(['email' => 'gl-admin@4putra.test'], [
            'name' => 'Admin Google',
            'username' => 'gl-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'google_id' => 'g-gl-99',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'gl-admin',
            'password' => 'password123',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }

    public function test_route_google_whitelist_sudah_dihapus(): void
    {
        $admin = User::updateOrCreate(['email' => 'gl-admin@4putra.test'], [
            'name' => 'Admin Google',
            'username' => 'gl-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)->postJson('/admin/google-whitelist', [
            'email' => 'baru@gmail.com', 'nama' => 'Baru', 'role' => 'user',
        ])->assertStatus(404);

        $this->actingAs($admin)->deleteJson('/admin/google-whitelist/1')->assertStatus(404);
    }
}
