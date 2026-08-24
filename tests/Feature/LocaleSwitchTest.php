<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Session;
use Tests\TestCase;

class LocaleSwitchTest extends TestCase
{
    /**
     * Halaman publik wajib mengirim Vary: Cookie, Accept-Language
     * agar edge cache Vercel membedakan konten per bahasa.
     */
    public function test_public_pages_send_vary_header(): void
    {
        $response = $this->get('/facilities');

        $response->assertStatus(200);
        $this->assertStringContainsString('Cookie', $response->headers->get('Vary') ?? '');
        $this->assertStringContainsString('Accept-Language', $response->headers->get('Vary') ?? '');
    }

    /**
     * Rute switch bahasa wajib no-store agar redirect tidak di-cache,
     * dan session locale tersimpan.
     */
    public function test_lang_switch_sets_session_and_no_store_header(): void
    {
        $response = $this->get('/lang/en');

        $response->assertRedirect();
        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control') ?? '');
        $this->assertSame('en', Session::get('locale'));
    }

    /**
     * Switch bahasa wajib mengirim cookie 'locale' (mengaktifkan Vary: Cookie
     * di CachePublic) dan redirect ke halaman asal dengan cache-buster ?v=
     * agar tidak dilayani cache HTTP/edge/prefetch berbahasa lama.
     */
    public function test_lang_switch_sets_locale_cookie_and_busts_target_cache(): void
    {
        $response = $this->get('/lang/en', ['Referer' => 'http://localhost/facilities']);

        $response->assertRedirect();
        $this->assertStringContainsString('/facilities?v=', $response->headers->get('Location'));
        $this->assertSame('en', $response->getCookie('locale')->getValue());
    }

    /**
     * Locale dari session diterapkan oleh middleware Localization.
     */
    public function test_locale_applied_from_session(): void
    {
        Session::put('locale', 'en');

        $response = $this->get('/facilities');

        $response->assertStatus(200);
        $this->assertSame('en', app()->getLocale());
    }
}
