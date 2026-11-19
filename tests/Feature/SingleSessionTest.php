<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Sesi tunggal (opsi A — kick yang lama): login baru di perangkat mana pun
 * menggantikan tiket sesi akun. Perangkat lama di-logout paksa dengan
 * pemberitahuan jelas — banner di halaman biasa, toast/JSON di AJAX.
 * Multi-tab browser yang sama aman (satu cookie = satu sesi = satu tiket).
 */
class SingleSessionTest extends TestCase
{
    protected function tearDown(): void
    {
        User::where('email', 'sesi-tunggal-test@4putra.test')->delete();
        User::where('email', 'sesi-tunggal-google-test@4putra.test')->delete();

        parent::tearDown();
    }

    private function buatAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'sesi-tunggal-test@4putra.test'],
            ['name' => 'Admin Sesi', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    public function test_login_baru_menggantikan_tiket_di_database(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $this->assertNotEmpty(
            $admin->fresh()->active_session_token,
            'Login wajib menerbitkan tiket sesi.'
        );
        $tiketAwal = $admin->fresh()->active_session_token;

        // Simulasi perangkat kedua: sesi baru (logout supaya guest middleware tak memblok)
        $this->app['auth']->guard()->logout();
        $this->flushSession();

        // Login kedua (perangkat lain) → tiket baru menggantikan tiket lama
        $this->post('/login', ['username' => $admin->username, 'password' => 'password123'])
            ->assertRedirect(route('admin.dashboard'));

        $tiketBaru = $admin->fresh()->active_session_token;
        $this->assertNotEmpty($tiketBaru);
        $this->assertNotSame(
            $tiketAwal,
            $tiketBaru,
            'Tiket wajib berganti saat login baru.'
        );
    }

    public function test_perangkat_lama_dikick_dengan_banner_sesi_digantikan(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);
        $tiketLama = $admin->fresh()->active_session_token;

        // Login di perangkat lain → tiket di DB berganti (sesi baru dulu)
        $this->app['auth']->guard()->logout();
        $this->flushSession();
        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);

        // Perangkat lama: tiket session masih tiket lama, DB sudah tiket baru
        $admin->refresh(); // muat tiket DB ke instance — actingAs memakai instance ini
        $this->withSession(['sesi_token' => $tiketLama]);
        $this->actingAs($admin);

        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('sesi_digantikan', 'Akun Anda telah login di perangkat lain. Sesi ini diakhiri otomatis.');

        $this->assertGuest('web', 'Perangkat lama wajib di-logout paksa.');
    }

    public function test_ajax_perangkat_lama_menerima_401_sesi_digantikan(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);
        $tiketLama = $admin->fresh()->active_session_token;

        // Login kedua di perangkat lain (sesi baru dulu)
        $this->app['auth']->guard()->logout();
        $this->flushSession();
        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);

        // Perangkat lama kirim AJAX (ping) → 401 JSON sesi_digantikan
        $admin->refresh(); // muat tiket DB ke instance — actingAs memakai instance ini
        $this->withSession(['sesi_token' => $tiketLama]);
        $this->actingAs($admin)
            ->get('/admin/ping', [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertStatus(401)
            ->assertJson([
                'ok' => false,
                'sesi_digantikan' => true,
            ]);
    }

    public function test_logout_merevoke_tiket_dan_membasikan_sesi_lain(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);
        $tiket = $admin->fresh()->active_session_token;

        // Logout manual merevoke tiket (sentinel 'revoked' — bukan null,
        // supaya sesi lain yang masih hidup tidak bisa mengklaim kembali)
        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertSame('revoked', $admin->fresh()->active_session_token, 'Logout wajib merevoke tiket sesi.');

        // Perangkat yang masih memegang tiket lama → dikick
        $admin->refresh(); // muat tiket DB ke instance — actingAs memakai instance ini
        $this->withSession(['sesi_token' => $tiket]);
        $this->actingAs($admin);
        $this->get(route('admin.dashboard'))
            ->assertRedirect(route('login'))
            ->assertSessionHas('sesi_digantikan');
    }

    public function test_google_login_merotasi_tiket_sesi(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'sesi-tunggal-google-test@4putra.test'],
            ['name' => 'Admin Google Sesi', 'username' => 'sesi-google', 'password' => bcrypt('password123'), 'role' => 'admin']
        );

        // Login username dulu → tiket pertama
        $this->post('/login', ['username' => 'sesi-google', 'password' => 'password123']);
        $tiketAwal = $admin->fresh()->active_session_token;
        $this->assertNotEmpty($tiketAwal);

        // Login Google (perangkat lain) → tiket berganti
        $abstractUser = \Mockery::mock(\Laravel\Socialite\Two\User::class);
        $abstractUser->shouldReceive('getId')->andReturn('g-sesi-1');
        $abstractUser->shouldReceive('getEmail')->andReturn('sesi-tunggal-google-test@4putra.test');
        $abstractUser->shouldReceive('getName')->andReturn('Admin Google Sesi');
        \Laravel\Socialite\Facades\Socialite::shouldReceive('driver')->with('google')->andReturnSelf();
        \Laravel\Socialite\Facades\Socialite::shouldReceive('redirectUrl')->andReturnSelf();
        \Laravel\Socialite\Facades\Socialite::shouldReceive('user')->andReturn($abstractUser);

        $this->get('/auth/google/callback')->assertRedirect(route('admin.dashboard'));

        $tiketBaru = $admin->fresh()->active_session_token;
        $this->assertNotEmpty($tiketBaru);
        $this->assertNotSame($tiketAwal, $tiketBaru, 'Login Google wajib merotasi tiket sesi.');
    }

    public function test_multi_tab_browser_sama_aman(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);
        $admin->refresh(); // muat tiket DB ke instance — actingAs memakai instance ini
        $tiket = $admin->active_session_token;

        // Multi-tab = satu sesi cookie = satu tiket — request berulang tetap masuk
        for ($i = 0; $i < 3; $i++) {
            $this->withSession(['sesi_token' => $tiket]);
            $this->actingAs($admin)
                ->get('/admin/ping', ['Accept' => 'application/json'])
                ->assertOk()
                ->assertJson(['ok' => true]);
        }
    }

    public function test_kick_tidak_membocorkan_data_sensitif_di_payload(): void
    {
        $admin = $this->buatAdmin();

        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);
        $tiketLama = $admin->fresh()->active_session_token;

        // Login kedua di perangkat lain (sesi baru dulu)
        $this->app['auth']->guard()->logout();
        $this->flushSession();
        $this->post('/login', ['username' => $admin->username, 'password' => 'password123']);

        // Perangkat lama kirim AJAX → payload kick wajib minimal (tanpa data user)
        $admin->refresh(); // muat tiket DB ke instance — actingAs memakai instance ini
        $this->withSession(['sesi_token' => $tiketLama]);
        $response = $this->actingAs($admin)
            ->get('/admin/ping', [
                'Accept' => 'application/json',
                'X-Requested-With' => 'XMLHttpRequest',
            ])
            ->assertStatus(401);

        $payload = $response->json();
        $this->assertSame(['ok', 'message', 'sesi_digantikan'], array_keys($payload));
        $this->assertArrayNotHasKey('user', $payload);
    }
}
