<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Smoke render semua halaman publik + admin: memastikan tidak ada ParseError,
 * view rusak, atau 500 saat halaman diakses sungguhan (aturan AGENTS.md).
 */
class SmokeRenderTest extends TestCase
{
    private function makeAdmin(): User
    {
        return User::updateOrCreate(
            ['email' => 'admin-smoke-test@4putra.test'],
            [
                'name' => 'Admin Smoke Test',
                'password' => bcrypt('password123'),
                'role' => 'admin',
            ]
        );
    }

    private function assertRenderable(string $url): void
    {
        $response = $this->get($url);
        $this->assertTrue(
            in_array($response->status(), [200, 302]),
            "{$url} gagal render: HTTP {$response->status()}"
        );
        if ($response->status() === 200) {
            $this->assertStringNotContainsString('ParseError', $response->getContent(), "{$url} mengandung ParseError");
        }
    }

    // ===================== PUBLIK =====================

    public function test_public_pages_render_without_errors(): void
    {
        foreach (['/', '/collections', '/achievements', '/facilities', '/daily-activities', '/about', '/login'] as $url) {
            $this->assertRenderable($url);
        }
    }

    public function test_contact_redirects_to_about_anchor(): void
    {
        $this->get('/contact')->assertRedirect('/about#contact');
    }

    // ===================== ADMIN =====================

    public function test_admin_pages_render_for_admin(): void
    {
        $admin = $this->makeAdmin();

        $urls = [
            '/admin',
            '/admin/profile',
            '/admin/achievements',
            '/admin/collections',
            '/admin/daily-activities',
            '/admin/facilities',
            '/admin/users',
            '/admin/chatbot',
            '/admin/chatbot/inventaris',
            '/admin/chatbot/transaksi',
            '/admin/chatbot/chat',
        ];

        foreach ($urls as $url) {
            $response = $this->actingAs($admin)->get($url);
            $this->assertTrue(
                in_array($response->status(), [200, 302]),
                "{$url} gagal render: HTTP {$response->status()}"
            );
            if ($response->status() === 200) {
                $this->assertStringNotContainsString('ParseError', $response->getContent(), "{$url} mengandung ParseError");
            }
        }

        $admin->delete();
    }

    public function test_admin_pages_require_auth(): void
    {
        $this->get('/admin')->assertRedirect(route('login'));
        $this->get('/admin/users')->assertRedirect(route('login'));
        $this->get('/admin/chatbot')->assertRedirect(route('login'));
    }

    protected function tearDown(): void
    {
        Cache::flush();
        parent::tearDown();
    }
}
