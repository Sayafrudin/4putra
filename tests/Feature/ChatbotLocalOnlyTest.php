<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

/**
 * Menu & route chatbot WA = fitur skripsi, local-only.
 * Di produksi (Vercel): menu tidak dirender + semua URL /admin/chatbot/* → 404.
 */
class ChatbotLocalOnlyTest extends TestCase
{
    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::updateOrCreate(
            ['email' => 'admin-chatbot-env-test@4putra.test'],
            ['name' => 'Admin Chatbot Env', 'password' => bcrypt('password123'), 'role' => 'admin']
        );
    }

    protected function tearDown(): void
    {
        User::where('email', 'admin-chatbot-env-test@4putra.test')->delete();
        parent::tearDown();
    }

    public function test_chatbot_404_di_produksi(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->actingAs($this->admin)->get('/admin/chatbot')->assertStatus(404);
        $this->actingAs($this->admin)->get('/admin/chatbot/transaksi')->assertStatus(404);
        $this->actingAs($this->admin)
            ->post('/admin/chatbot/chat/send', [], ['X-CSRF-TOKEN' => csrf_token()])
            ->assertStatus(404);

        // Tamu tak-login: grup induk admin.auth lebih dulu → redirect login
        // (perilaku sama dengan route /admin lain; setelah login, local.only membalas 404)
        $this->app->make('auth')->forgetGuards();
        $this->get('/admin/chatbot')->assertRedirect(route('login'));
    }

    public function test_menu_chatbot_tidak_dirender_di_produksi(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertDontSee('Chatbot WhatsApp');
    }

    public function test_chatbot_tetap_tersedia_di_luar_produksi(): void
    {
        // Env testing (phpunit) = di luar produksi → chatbot harus tetap hidup untuk skripsi/dev
        $this->actingAs($this->admin)->get('/admin/chatbot')->assertOk();
        $this->actingAs($this->admin)->get('/admin')->assertOk()->assertSee('Chatbot WhatsApp');
    }
}
