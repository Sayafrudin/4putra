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
}
