<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Redirect sadar-role setelah login: pengguna non-admin tidak boleh
 * diarahkan ke /admin (jebakan 403 tanpa pintu keluar), admin tetap
 * menuju dashboard via intended. Halaman 403 wajib menyediakan tombol
 * keluar agar sesi non-admin tidak terjebak selamanya.
 */
class RoleAwareLoginRedirectTest extends TestCase
{
    private array $emails = [
        'roleaware-user@4putra.test',
        'roleaware-admin@4putra.test',
        'roleaware-google@4putra.test',
    ];

    protected function tearDown(): void
    {
        User::whereIn('email', $this->emails)->delete();

        parent::tearDown();
    }

    public function test_non_admin_email_login_redirects_to_home(): void
    {
        User::updateOrCreate(['email' => 'roleaware-user@4putra.test'], [
            'name' => 'Pengguna Biasa',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        // Sesi guest sempat menyasar /admin (url.intended terisi) —
        // non-admin TIDAK boleh dikembalikan ke sana oleh intended().
        $this->get(route('admin.facilities.index'));

        $this->post('/login', [
            'email' => 'roleaware-user@4putra.test',
            'password' => 'password123',
        ])->assertRedirect(url('/'));
    }

    public function test_admin_email_login_redirects_to_intended_admin_url(): void
    {
        User::updateOrCreate(['email' => 'roleaware-admin@4putra.test'], [
            'name' => 'Admin Roleaware',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->get(route('admin.facilities.index'));

        $this->post('/login', [
            'email' => 'roleaware-admin@4putra.test',
            'password' => 'password123',
        ])->assertRedirect(url('/admin/facilities'));
    }

    public function test_non_admin_google_callback_redirects_to_home(): void
    {
        User::updateOrCreate(['email' => 'roleaware-google@4putra.test'], [
            'name' => 'Pengguna Google',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'google_id' => 'g-roleaware-123',
        ]);

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-roleaware-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('roleaware-google@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Pengguna Google');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        $this->get('/auth/google/callback')->assertRedirect(url('/'));
    }

    public function test_forbidden_page_offers_logout_escape(): void
    {
        $user = User::updateOrCreate(['email' => 'roleaware-user@4putra.test'], [
            'name' => 'Pengguna Biasa',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $response = $this->actingAs($user)->get('/admin');

        $response->assertStatus(403);
        $response->assertSee(route('logout'), false);
        $response->assertSee('Keluar');
    }
}
