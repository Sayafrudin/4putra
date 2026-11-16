<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Export laporan Excel (.xlsx, sisi browser) + PDF (DomPDF) untuk
 * halaman Vaksinasi, Telur & Incubator, dan Keuangan.
 */
class AdminExportTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-export-test@4putra.test'],
            ['name' => 'Admin Export', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        User::where('email', 'admin-export-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_pdf_vaksinasi_telur_keuangan_terlindungi_auth(): void
    {
        $this->get('/admin/vaksinasi/export/pdf')->assertRedirect(route('login'));
        $this->get('/admin/telur/export/pdf')->assertRedirect(route('login'));
        $this->get('/admin/keuangan/export/pdf')->assertRedirect(route('login'));
    }

    public function test_pdf_vaksinasi_telur_keuangan_menghasilkan_file_pdf(): void
    {
        $this->actingAs($this->admin)->get('/admin/vaksinasi/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->admin)->get('/admin/telur/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        $this->actingAs($this->admin)->get('/admin/keuangan/export/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');

        // Export keuangan mengikuti filter aktif (tipe/kategori)
        $this->actingAs($this->admin)->get('/admin/keuangan/export/pdf?tipe=keluar&kategori=Gaji')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_halaman_memuat_tombol_dan_data_ekspor_excel(): void
    {
        $this->actingAs($this->admin)->get('/admin/vaksinasi')
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertSee('DATA_EKSPOR');

        $this->actingAs($this->admin)->get('/admin/telur')
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertSee('DATA_EKSPOR');

        $this->actingAs($this->admin)->get('/admin/keuangan')
            ->assertOk()
            ->assertSee('Export Excel')
            ->assertSee('Export PDF')
            ->assertSee('DATA_EKSPOR');
    }
}
