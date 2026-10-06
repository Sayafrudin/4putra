<?php

namespace Tests\Feature;

use Tests\TestCase;

class AdminDomainTest extends TestCase
{
    public function test_admin_404_di_host_produksi(): void
    {
        // Admin dashboard local-only: host produksi wajib 404 (sebelum auth redirect).
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
}
