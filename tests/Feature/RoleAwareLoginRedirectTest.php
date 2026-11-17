<?php

namespace Tests\Feature;

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Mockery;
use Tests\TestCase;

/**
 * Redirect sadar-role setelah login: pengguna non-admin di host publik tidak
 * boleh diarahkan ke /admin (AdminDomain 404), admin tetap menuju dashboard
 * via intended. Role user BOLEH masuk dashboard admin (menu terbatas):
 * menu monitoring terbuka penuh, kelola user & manajemen konten ditolak
 * server-side (403) dengan tombol tunggal kembali ke halaman login.
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
            'username' => 'roleaware-user',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        // Sesi guest sempat menyasar /admin (url.intended terisi) —
        // non-admin TIDAK boleh dikembalikan ke sana oleh intended().
        $this->get(route('admin.facilities.index'));

        $this->post('/login', [
            'username' => 'roleaware-user',
            'password' => 'password123',
        ])->assertRedirect(url('/'));
    }

    public function test_admin_email_login_redirects_to_intended_admin_url(): void
    {
        User::updateOrCreate(['email' => 'roleaware-admin@4putra.test'], [
            'name' => 'Admin Roleaware',
            'username' => 'roleaware-admin',
            'password' => bcrypt('password123'),
            'role' => 'admin',
        ]);

        $this->get(route('admin.facilities.index'));

        $this->post('/login', [
            'username' => 'roleaware-admin',
            'password' => 'password123',
        ])->assertRedirect(url('/admin/facilities'));
    }

    public function test_non_admin_google_callback_redirects_to_home(): void
    {
        User::updateOrCreate(['email' => 'roleaware-google@4putra.test'], [
            'name' => 'Pengguna Google',
            'username' => 'roleaware-google',
            'password' => bcrypt('password123'),
            'role' => 'user',
            'google_id' => 'g-roleaware-123',
        ]);

        $abstractUser = Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-roleaware-123');
        $abstractUser->shouldReceive('getEmail')->andReturn('roleaware-google@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Pengguna Google');

        Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        Socialite::shouldReceive('user')->andReturn($abstractUser);

        $this->get('/auth/google/callback')->assertRedirect(url('/'));
    }

    public function test_role_user_bisa_masuk_dashboard_admin(): void
    {
        $user = User::updateOrCreate(['email' => 'roleaware-user@4putra.test'], [
            'name' => 'Pengguna Biasa',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($user)->get('/admin')->assertOk();
    }

    public function test_role_user_ditolak_di_kelola_user_dan_manajemen_konten(): void
    {
        $user = User::updateOrCreate(['email' => 'roleaware-user@4putra.test'], [
            'name' => 'Pengguna Biasa',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        $this->actingAs($user)->get('/admin/users')->assertStatus(403);
        $this->actingAs($user)->get('/admin/collections')->assertStatus(403);
        $this->actingAs($user)->get('/admin/achievements')->assertStatus(403);
        $this->actingAs($user)->get('/admin/daily-activities')->assertStatus(403);
        $this->actingAs($user)->get('/admin/facilities')->assertStatus(403);
    }

    public function test_halaman_403_satu_tombol_kembali_ke_login(): void
    {
        $user = User::updateOrCreate(['email' => 'roleaware-user@4putra.test'], [
            'name' => 'Pengguna Biasa',
            'password' => bcrypt('password123'),
            'role' => 'user',
        ]);

        // Tombol tunggal POST /admin/logout: path /admin/* tidak dibajak
        // AdminHostRedirect di subdomain admin (POST /logout lama terbawa
        // redirect balik ke /admin sehingga 403 terus-menerus).
        $response = $this->actingAs($user)->get('/admin/users');

        $response->assertStatus(403);
        $response->assertSee(route('admin.logout'), false);
        $response->assertSee('Kembali ke Halaman Login');
        $response->assertDontSee('Kembali ke Beranda');
        $response->assertDontSee(route('logout'), false);
    }
}
