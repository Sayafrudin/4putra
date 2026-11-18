<?php

namespace Tests\Feature;

use App\Models\User;
use App\Support\AdminMenus;
use App\Support\DashboardStats;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

/**
 * Dashboard profesional + PINTU TUNGGAL angka agregat:
 * - DashboardStats::untuk() menyarikan per hak akses menu (tanpa kunci = tanpa data).
 * - Halaman dashboard tidak boleh memuat angka yang berada di luar hak akses.
 * - Agregasi keuangan hanya boleh lewat pintu (test penjaga arsitektur).
 */
class DashboardStatsTest extends TestCase
{
    protected function tearDown(): void
    {
        User::whereIn('email', [
            'dash-admin@4putra.test', 'dash-terbatas@4putra.test', 'dash-kering@4putra.test',
        ])->delete();
        parent::tearDown();
    }

    private function buatUser(string $email, string $role, ?array $menuAccess): User
    {
        return User::updateOrCreate(['email' => $email], [
            'name' => 'Uji Dashboard', 'username' => str_replace(['@', '.'], ['-', '-'], $email),
            'password' => bcrypt('password123'), 'role' => $role, 'menu_access' => $menuAccess,
        ]);
    }

    public function test_admin_mendapat_semua_kunci_agregat(): void
    {
        $admin = $this->buatUser('dash-admin@4putra.test', 'admin', null);

        $stats = DashboardStats::untuk($admin);

        $this->assertArrayHasKey('burung', $stats);
        $this->assertArrayHasKey('keuangan', $stats);
        $this->assertArrayHasKey('kas30', $stats);
        $this->assertArrayHasKey('stok', $stats);
        $this->assertArrayHasKey('telur', $stats);
        $this->assertArrayHasKey('vaksin', $stats);
        $this->assertArrayHasKey('transaksiTerbaru', $stats);
        $this->assertArrayHasKey('aktivitasTerbaru', $stats);
    }

    public function test_role_user_tanpa_akses_keuangan_tidak_menerima_data_keuangan(): void
    {
        // Menu: hanya kandang — tanpa keuangan, stok, telur, vaksinasi, aktivitas
        $terbatas = $this->buatUser('dash-terbatas@4putra.test', 'user', ['kandang']);

        $stats = DashboardStats::untuk($terbatas);

        $this->assertArrayHasKey('burung', $stats);
        $this->assertArrayNotHasKey('keuangan', $stats, 'Data keuangan tidak boleh keluar pintu untuk role tanpa akses');
        $this->assertArrayNotHasKey('kas30', $stats);
        $this->assertArrayNotHasKey('stok', $stats);
        $this->assertArrayNotHasKey('telur', $stats);
        $this->assertArrayNotHasKey('vaksin', $stats);
    }

    public function test_halaman_dashboard_tidak_membocorkan_angka_di_luar_hak_akses(): void
    {
        $terbatas = $this->buatUser('dash-terbatas@4putra.test', 'user', ['kandang']);

        $res = $this->actingAs($terbatas)->get('/admin')->assertOk();

        // Label kartu keuangan tidak boleh ter-render untuk role tanpa akses
        $res->assertDontSee('Saldo Kas Umum', false);
        $res->assertDontSee('Arus Kas', false);
        $res->assertSee('Total Burung', false);
    }

    public function test_agregasi_keuangan_hanya_boleh_lewat_pintu_tunggal(): void
    {
        // Test penjaga arsitektur: agregasi tabel keuangans (sum/pluck/avg/raw)
        // HANYA boleh di pintu (DashboardStats) & controller/view resminya.
        // Pelanggaran = suite merah; menonaktifkan penjaga ini = perubahan yang
        // terlihat jelas di review.
        $diizinkan = ['KeuanganController.php', 'FinancialTab.php', 'DashboardStats.php'];
        $polaLarangan = [
            '/Keuangan\s*::\s*(sum|avg|min|max|pluck)\s*\(/i',
            "/DB\s*::\s*table\s*\(\s*['\"]keuangans['\"]/i",
            "/->from\s*\(\s*['\"]keuangans['\"]/i",
        ];

        $pelanggaran = [];
        foreach (new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(app_path(), \FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }
            if (in_array($file->getFilename(), $diizinkan, true)) {
                continue;
            }
            $isi = file_get_contents($file->getPathname());
            foreach ($polaLarangan as $pola) {
                if (preg_match($pola, $isi)) {
                    $pelanggaran[] = $file->getFilename();
                    break;
                }
            }
        }

        $this->assertSame([], $pelanggaran, 'Agregasi keuangan di luar pintu DashboardStats: '.implode(', ', $pelanggaran));
    }

    public function test_semua_route_admin_tetap_terklasifikasi(): void
    {
        // Sanity: penjaga registry (dari AdminMenuAccessTest) tidak ikut rusak saat
        // dashboard ditambahkan — duplikasi ringan demi keterbacaan test ini sendiri.
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
        $this->assertSame([], $belumTerdaftar);
    }
}
