<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Kontrak sesi baru (ala MyBCA): TIDAK ADA remember-me.
 * - Login (username/password maupun Google) tidak menerbitkan cookie remember.
 * - Sesi yang mati di server tidak dibangkitkan ulang diam-diam: cookie
 *   remember lama (sisa deploy sebelumnya) harus diabaikan.
 * - Logout tetap bersih: sesi hancur, /admin kembali terkunci.
 */
class AdminRememberMeTest extends TestCase
{
    private function makeAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin-remember-test@4putra.test'],
            [
                'name' => 'Admin Remember Test',
                'username' => 'admin-remember-test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
                'remember_token' => Str::random(60),
            ]
        );
    }

    public function test_login_username_tidak_menerbitkan_cookie_remember(): void
    {
        $user = $this->makeAdmin();

        $response = $this->post('/login', [
            'username' => 'admin-remember-test',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $recaller = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNull($recaller, 'Cookie remember TIDAK boleh diterbitkan saat login');

        $user->delete();
    }

    public function test_login_google_tidak_menerbitkan_cookie_remember(): void
    {
        $user = $this->makeAdmin();

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-remember-1');
        $abstractUser->shouldReceive('getEmail')->andReturn('admin-remember-test@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Admin Remember Test');
        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        $response = $this->get('/auth/google/callback');
        $response->assertRedirect();
        $this->assertAuthenticated();

        $recaller = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNull($recaller, 'Cookie remember TIDAK boleh diterbitkan saat login Google');

        $user->delete();
    }

    public function test_cookie_remember_lama_tidak_membangkitkan_sesi_mati(): void
    {
        $user = $this->makeAdmin();

        $response = $this->post('/login', [
            'username' => 'admin-remember-test',
            'password' => 'password123',
        ]);
        $response->assertRedirect();

        // Cookie recaller "sah" dengan format id|token|hash (sisa deploy lama),
        // lalu token di server dirotasi (prosedur pasca-deploy) → cookie basi.
        $namaRecaller = 'remember_web_'.sha1(config('auth.defaults.guard'));
        $this->withUnencryptedCookies([
            $namaRecaller => $user->id.'|'.$user->remember_token.'|'.$user->password,
        ]);
        $user->forceFill(['remember_token' => Str::random(60)])->save();

        // Sesi dihancurkan (simulasi tab dibekukan melewati lifetime) dan guard
        // di-reset — tanpa ini, objek user dari login POST masih tertanam di
        // memori guard dalam proses test yang sama.
        $store = $this->app['session.store'];
        $store->flush();
        $store->getHandler()->destroy($store->getId());
        $this->app['auth']->forgetGuards();

        // Sesi tidak boleh dibangkitkan ulang diam-diam — ping harus 401
        $this->getJson('/admin/ping')->assertStatus(401);
        $this->assertNull(Auth::id(), 'Sesi mati tidak boleh dibangkitkan via cookie remember');

        $user->delete();
    }

    public function test_logout_mengakhiri_sesi_dengan_bersih(): void
    {
        $user = $this->makeAdmin();

        $this->post('/login', [
            'username' => 'admin-remember-test',
            'password' => 'password123',
        ])->assertRedirect();
        $this->assertAuthenticated();
        $this->get('/admin')->assertOk();

        $this->post('/logout')->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/admin')->assertRedirect(route('login'));

        $user->delete();
    }

    public function test_guest_ping_without_remember_stays_401(): void
    {
        $this->getJson('/admin/ping')->assertStatus(401);
    }
}
