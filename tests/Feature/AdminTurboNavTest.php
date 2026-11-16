<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Navigasi sidebar SPA (Turbo Drive opt-in): link sidebar ber-atribut data-turbo="true",
 * Drive global tetap nonaktif (html data-turbo="false") agar form & link lain klasik.
 */
class AdminTurboNavTest extends TestCase
{
    public function test_sidebar_link_opt_in_turbo_dan_layout_membedai_cache(): void
    {
        $admin = User::updateOrCreate(
            ['email' => 'admin-turbo-test@4putra.test'],
            ['name' => 'Admin Turbo', 'password' => bcrypt('password123'), 'role' => 'admin']
        );

        $this->actingAs($admin)->get('/admin')
            ->assertOk()
            ->assertSee('data-turbo="true"', false)
            ->assertSee('turbo-cache-control', false)
            ->assertSee('data-turbo="false"', false);

        User::where('email', 'admin-turbo-test@4putra.test')->delete();
    }
}
