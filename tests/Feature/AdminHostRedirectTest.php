<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminHostRedirectTest extends TestCase
{
    public function test_root_host_admin_redirect_ke_admin(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://management4putra-m4p.vercel.app/')
            ->assertRedirect('http://management4putra-m4p.vercel.app/admin');
    }

    public function test_halaman_publik_di_host_admin_redirect_ke_admin(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://management4putra-m4p.vercel.app/collections')
            ->assertRedirect('http://management4putra-m4p.vercel.app/admin');
    }

    public function test_path_admin_di_host_admin_tidak_dialihkan(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        // /admin lolos AdminHostRedirect → AdminDomain lolos → tanpa auth redirect login
        $this->get('http://management4putra-m4p.vercel.app/admin')
            ->assertRedirect(route('login'));
    }

    public function test_root_host_publik_tidak_terganggu(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://4putra.vercel.app/')->assertOk();
    }

    public function test_root_local_tidak_terganggu(): void
    {
        // Tanpa ADMIN_HOST ter-set (local dev) halaman publik normal
        config(['app.admin_host' => null]);

        $this->get('/')->assertOk();
    }

    public function test_rantai_redirect_host_admin_berakhir_di_login(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        // Regresi loop /admin <-> /login: rantai penuh / -> /admin -> /login harus berhenti 200
        $this->followingRedirects()
            ->get('http://management4putra-m4p.vercel.app/')
            ->assertOk();
    }

    public function test_login_di_host_admin_tidak_dialihkan(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        // /login = tujuan auth middleware di host admin; wajib lolos tanpa redirect
        $this->get('http://management4putra-m4p.vercel.app/login')->assertOk();
    }

    public function test_google_auth_di_host_admin_tidak_dialihkan_ke_admin(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        // OAuth login admin: /auth/google harus mengarah ke Google, bukan diputar balik ke /admin
        $response = $this->get('http://management4putra-m4p.vercel.app/auth/google');

        $response->assertRedirect();
        $this->assertStringNotContainsString('/admin', $response->headers->get('Location'));
    }

    public function test_storage_di_host_admin_tidak_dialihkan(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        // Gambar lokal dirujuk halaman admin via /storage/*; file tidak ada = 404, bukan redirect
        $this->get('http://management4putra-m4p.vercel.app/storage/collections/tidak-ada.jpg')
            ->assertStatus(404);
    }

    public function test_health_up_di_host_admin_tidak_dialihkan(): void
    {
        config(['app.admin_host' => 'management4putra-m4p.vercel.app']);

        $this->get('http://management4putra-m4p.vercel.app/up')->assertOk();
    }
}
