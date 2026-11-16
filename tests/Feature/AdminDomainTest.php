<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDomainTest extends TestCase
{
    public function test_admin_404_di_host_produksi(): void
    {
        // Host publik produksi wajib 404 (admin hanya di subdomain ADMIN_HOST).
        // Full URL wajib: get() membangun URL dari APP_URL (localhost), yang menimpa HTTP_HOST.
        $this->get('http://4putra.vercel.app/admin')->assertStatus(404);
    }

    public function test_admin_subpath_404_di_host_produksi(): void
    {
        $this->get('http://4putra.vercel.app/admin/users')->assertStatus(404);
    }

    public function test_admin_tidak_404_di_local(): void
    {
        // Host local → middleware lolos; tanpa auth = redirect login (302), bukan 404
        $this->get('/admin')->assertRedirect(route('login'));
    }

    public function test_admin_bisa_diakses_di_host_admin_produksi(): void
    {
        // Petinggi mengakses admin lewat subdomain khusus (env ADMIN_HOST, tidak di-hardcode)
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://management4putra-m4p.vercel.app/admin')->assertRedirect(route('login'));
        $this->get('http://management4putra-m4p.vercel.app/admin/telur')->assertRedirect(route('login'));
    }

    public function test_admin_tetap_404_di_host_lain_sewaktu_host_admin_diaktifkan(): void
    {
        // Saat host admin aktif, host publik & host asing TETAP 404
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://4putra.vercel.app/admin')->assertStatus(404);
        $this->get('http://host-asing.example.com/admin')->assertStatus(404);
    }
}
