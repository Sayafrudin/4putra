<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * Alur "Perpanjang Sesi": ketika sesi web mati di server (tab dibekukan
 * browser / laptop sleep melewati SESSION_LIFETIME), cookie remember harus
 * memulihkan sesi otomatis — ping /admin/ping tetap 200 tanpa login ulang.
 */
class AdminRememberMeTest extends TestCase
{
    private function makeAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin-remember-test@4putra.test'],
            [
                'name' => 'Admin Remember Test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );
    }

    public function test_login_always_issues_remember_cookie_for_14_days(): void
    {
        $user = $this->makeAdmin();

        $response = $this->post('/login', [
            'email' => 'admin-remember-test@4putra.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $this->assertAuthenticated();

        $recaller = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));

        $this->assertNotNull($recaller, 'Cookie remember wajib diterbitkan saat login');
        // Toleransi 1 detik: Max-Age dihitung dari timestamp Expires yang membulat
        $this->assertEqualsWithDelta(14 * 24 * 60 * 60, $recaller->getMaxAge(), 1);

        $user->delete();
    }

    public function test_ping_recovers_dead_session_via_remember_cookie(): void
    {
        $user = $this->makeAdmin();

        $response = $this->post('/login', [
            'email' => 'admin-remember-test@4putra.test',
            'password' => 'password123',
        ]);
        $response->assertRedirect();

        $recaller = collect($response->headers->getCookies())
            ->first(fn ($c) => str_starts_with($c->getName(), 'remember_web_'));
        $this->assertNotNull($recaller);

        // Simulasi tab dibekukan melewati lifetime: payload sesi dihancurkan
        // (sama seperti cookie sesi hilang dari browser), hanya cookie
        // remember yang tersisa dan direplay oleh browser.
        $store = $this->app['session.store'];
        $store->flush();
        $store->getHandler()->destroy($store->getId());

        // Tombol "Perpanjang Sesi" memanggil /admin/ping → harus recall otomatis
        $ping = $this->withUnencryptedCookies([$recaller->getName() => $recaller->getValue()])
            ->getJson('/admin/ping');

        $ping->assertStatus(200)->assertJson(['ok' => true]);
        $this->assertSame((string) $user->id, (string) Auth::id(), 'Sesi wajib dipulihkan otomatis via remember cookie');

        $user->delete();
    }

    public function test_guest_ping_without_remember_stays_401(): void
    {
        $this->getJson('/admin/ping')->assertStatus(401);
    }
}
