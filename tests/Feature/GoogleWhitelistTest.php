<?php

namespace Tests\Feature;

use App\Models\GoogleWhitelist;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Whitelist Gmail Google: hanya email di daftar yang boleh login Google,
 * role diambil dari whitelist (dikelola admin). Gmail tak dikenal ditolak
 * dan TIDAK membuat akun apa pun. Akun Google-link dilarang login password.
 */
class GoogleWhitelistTest extends TestCase
{
    private array $emails = [
        'wl-admin@4putra.test',
        'wl-user@4putra.test',
        'wl-asing@gmail.com',
    ];

    protected function tearDown(): void
    {
        User::whereIn('email', $this->emails)->delete();
        GoogleWhitelist::whereIn('email', $this->emails)->delete();

        parent::tearDown();
    }

    private function mockGoogle(string $email, string $id = 'g-wl-1'): void
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
        $this->mockGoogle('wl-asing@gmail.com');

        $this->get('/auth/google/callback')
            ->assertRedirect(route('login'))
            ->assertSessionHas('error');

        $this->assertDatabaseMissing('users', ['email' => 'wl-asing@gmail.com']);
        Log::shouldHaveReceived('warning')->once()->withArgs(function (string $message, array $context) {
            return str_contains($message, 'whitelist') && $context['email'] === 'wl-asing@gmail.com';
        });
    }

    public function test_gmail_whitelist_user_dibuat_mengikuti_role_whitelist(): void
    {
        GoogleWhitelist::create(['email' => 'wl-user@4putra.test', 'role' => 'user']);
        $this->mockGoogle('wl-user@4putra.test');

        $this->get('/auth/google/callback')->assertRedirect(url('/'));

        $user = User::where('email', 'wl-user@4putra.test')->first();
        $this->assertNotNull($user, 'Akun wajib dibuat untuk Gmail terdaftar');
        $this->assertSame('user', $user->role);
        $this->assertNotNull($user->username, 'Username wajib ter-generate dari email');
        $this->assertNotEmpty($user->google_id);
    }

    public function test_role_diwhitelist_sinkron_ke_akun_di_setiap_login(): void
    {
        User::updateOrCreate(['email' => 'wl-admin@4putra.test'], [
            'name' => 'Whitelist Admin',
            'username' => 'wl-admin',
            'password' => bcrypt('password123'),
            'role' => 'user', // role lama beda dengan whitelist
            'google_id' => 'g-wl-99',
        ]);
        GoogleWhitelist::create(['email' => 'wl-admin@4putra.test', 'role' => 'admin']);

        $this->mockGoogle('wl-admin@4putra.test', 'g-wl-99');

        $this->get('/auth/google/callback')->assertRedirect(route('admin.dashboard'));

        $this->assertSame('admin', User::where('email', 'wl-admin@4putra.test')->value('role'));
    }

    public function test_akun_google_link_dilarang_login_username_password(): void
    {
        User::updateOrCreate(['email' => 'wl-admin@4putra.test'], [
            'name' => 'Whitelist Admin',
            'username' => 'wl-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
            'google_id' => 'g-wl-99',
        ]);

        $this->from('/login')->post('/login', [
            'username' => 'wl-admin',
            'password' => 'password123',
        ])->assertRedirect('/login')->assertSessionHasErrors('username');

        $this->assertGuest();
    }
}
