<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenus;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Hak akses menu per akun admin (users.menu_access):
 * - NULL = akses penuh (akun lama tak berubah perilaku).
 * - Daftar menu = hanya menu itu (fail-open: route tak terikat menu tetap boleh).
 * - Kelola Akun User & komentar aktivitas = eksklusif admin penuh.
 * - Registry config/admin_menus.php harus menutup SEMUA route admin (test peta).
 */
class AdminMenuAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        User::updateOrCreate(
            ['email' => 'admin-penuh-test@4putra.test'],
            ['name' => 'Admin Penuh', 'password' => bcrypt('password123'), 'role' => 'admin', 'menu_access' => null]
        );
        User::updateOrCreate(
            ['email' => 'admin-terbatas-test@4putra.test'],
            ['name' => 'Admin Terbatas', 'password' => bcrypt('password123'), 'role' => 'admin', 'menu_access' => ['keuangan']]
        );
        User::updateOrCreate(
            ['email' => 'admin-kosong-test@4putra.test'],
            ['name' => 'Admin Kosong', 'password' => bcrypt('password123'), 'role' => 'admin', 'menu_access' => []]
        );
    }

    protected function tearDown(): void
    {
        User::whereIn('email', [
            'admin-penuh-test@4putra.test', 'admin-terbatas-test@4putra.test', 'admin-kosong-test@4putra.test',
            'uji-menu-akses@4putra.test', 'uji-role-user-ma@4putra.test', 'uji-role-user-ma2@4putra.test',
        ])->delete();
        parent::tearDown();
    }

    public function test_admin_penuh_menu_access_null_bisa_akses_semua_menu(): void
    {
        $admin = User::where('email', 'admin-penuh-test@4putra.test')->first();

        $this->actingAs($admin)->get('/admin/keuangan')->assertOk();
        $this->actingAs($admin)->get('/admin/kandang')->assertOk();
        // Kelola Akun User hanya untuk admin penuh
        $this->actingAs($admin)->get('/admin/users')->assertOk();
    }

    public function test_admin_terbatas_hanya_menunya_yang_boleh(): void
    {
        $admin = User::where('email', 'admin-terbatas-test@4putra.test')->first();

        // Menu yang diberikan → boleh
        $this->actingAs($admin)->get('/admin/keuangan')->assertOk();

        // Menu di luar daftar → redirect ke dashboard (HTML) / 403 (JSON)
        $this->actingAs($admin)->get('/admin/kandang')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        $this->actingAs($admin)->postJson('/admin/burung', [])
            ->assertStatus(403)
            ->assertJson(['ok' => false]);

        // Kelola Akun User tetap terkunci meski route-nya "always allowed"
        $this->actingAs($admin)->get('/admin/users')->assertRedirect(route('admin.dashboard'));
    }

    public function test_admin_kosong_hanya_dashboard_dan_route_tak_terikat_menu(): void
    {
        $admin = User::where('email', 'admin-kosong-test@4putra.test')->first();

        $this->actingAs($admin)->get('/admin')->assertOk();
        // Fail-open: route tanpa mapping menu (ping) tetap hidup
        $this->actingAs($admin)->get('/admin/ping')->assertOk();
        $this->actingAs($admin)->get('/admin/kandang')->assertRedirect(route('admin.dashboard'));
    }

    public function test_sidebar_menyembunyikan_menu_di_luar_hak_akses(): void
    {
        $admin = User::where('email', 'admin-terbatas-test@4putra.test')->first();

        $res = $this->actingAs($admin)->get('/admin')->assertOk();
        $res->assertSee('href="'.route('admin.keuangan.index').'"', false);
        $res->assertDontSee('href="'.route('admin.kandang.index').'"', false);
        // Kelola Akun User hilang dari sidebar admin terbatas
        $res->assertDontSee('href="'.route('admin.users.index').'"', false);
    }

    public function test_tambah_user_menyimpan_menu_access_dan_menolak_kunci_asing(): void
    {
        $admin = User::where('email', 'admin-penuh-test@4putra.test')->first();

        // Kunci menu yang tidak dikenal → 422
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Uji Menu', 'username' => 'uji-menu-akses', 'email' => 'uji-menu-akses@4putra.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin', 'menu_access' => ['keuangan', 'kunci-palsu'],
        ])->assertSessionHasErrors('menu_access.1');

        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Uji Menu', 'username' => 'uji-menu-akses', 'email' => 'uji-menu-akses@4putra.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'admin', 'menu_access' => ['keuangan', 'kandang'],
        ])->assertSessionHasNoErrors();

        $baru = User::where('email', 'uji-menu-akses@4putra.test')->first();
        $this->assertEqualsCanonicalizing(['keuangan', 'kandang'], $baru->menu_access);
    }

    public function test_role_user_menyimpan_menu_access_sesuai_cakupan_role(): void
    {
        $admin = User::where('email', 'admin-penuh-test@4putra.test')->first();

        // Role user: hanya menu akses 'semua' yang sah (kunci admin diabaikan)
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Uji Role User', 'username' => 'uji-role-user-ma', 'email' => 'uji-role-user-ma@4putra.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'user', 'menu_access' => ['keuangan', 'kandang', 'collections'],
        ])->assertSessionHasNoErrors();

        $baru = User::where('email', 'uji-role-user-ma@4putra.test')->first();
        $this->assertEqualsCanonicalizing(['keuangan', 'kandang'], $baru->menu_access);

        // Role user + akses penuh → NULL (semua menu monitoring)
        $this->actingAs($admin)->post('/admin/users', [
            'name' => 'Uji Role User 2', 'username' => 'uji-role-user-ma2', 'email' => 'uji-role-user-ma2@4putra.test',
            'password' => 'password123', 'password_confirmation' => 'password123',
            'role' => 'user', 'akses_penuh' => 1, 'menu_access' => ['kandang'],
        ])->assertSessionHasNoErrors();

        $kedua = User::where('email', 'uji-role-user-ma2@4putra.test')->first();
        $this->assertNull($kedua->menu_access);
    }

    public function test_role_user_menu_access_mengatur_menu_monitoring(): void
    {
        $terbatas = User::updateOrCreate(
            ['email' => 'user-terbatas-test@4putra.test'],
            ['name' => 'Role Terbatas', 'password' => bcrypt('password123'), 'role' => 'user', 'menu_access' => ['keuangan']]
        );

        // Menu yang diberikan → boleh; di luar daftar → ditolak
        $this->actingAs($terbatas)->get('/admin/keuangan')->assertOk();
        $this->actingAs($terbatas)->get('/admin/kandang')
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('error');

        // Role user TANPA menu_access (akun lama) → monitoring penuh
        $bebas = User::updateOrCreate(
            ['email' => 'user-bebas-test@4putra.test'],
            ['name' => 'Role Bebas', 'password' => bcrypt('password123'), 'role' => 'user', 'menu_access' => null]
        );
        $this->actingAs($bebas)->get('/admin/kandang')->assertOk();

        User::whereIn('email', ['user-terbatas-test@4putra.test', 'user-bebas-test@4putra.test'])->delete();
    }

    /**
     * Test penjaga registry: SEMUA route admin harus terklasifikasi — milik menu
     * di config/admin_menus.php atau masuk daftar always_allowed. Route baru yang
     * lupa didaftarkan membuat test ini merah (mencegah drift fail-open diam-diam).
     */
    public function test_semua_route_admin_terklasifikasi_di_registry(): void
    {
        $belumTerdaftar = [];

        foreach (Route::getRoutes() as $route) {
            $name = $route->getName();
            if (! $name || ! str_starts_with($name, 'admin.')) {
                continue;
            }
            if (AdminMenus::isAlwaysAllowed($name) || AdminMenus::menuForRoute($name) !== null) {
                continue;
            }
            $belumTerdaftar[] = $name;
        }

        $this->assertSame([], $belumTerdaftar, 'Route admin belum terdaftar di config/admin_menus.php: '.implode(', ', $belumTerdaftar));
    }
}
