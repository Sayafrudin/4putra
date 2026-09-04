<?php

namespace Tests\Feature;

use Tests\TestCase;

class ResponsiveLayoutTest extends TestCase
{
    /**
     * Tablet (768-1023px): hamburger wajib aktif sampai lg:, menu inline
     * hanya tampil di lg+. Logo wajib shrink-0 agar tidak tercompress.
     */
    public function test_navbar_uses_lg_breakpoint_for_inline_menu(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('lg:hidden', $response->getContent());
        $this->assertStringContainsString('hidden lg:flex', $response->getContent());
        $this->assertStringNotContainsString('md:px-32', $response->getContent());
    }

    /**
     * Hero: kolom teks wajib dibatasi lebar di tablet agar tidak
     * mengalir ke area gambar macaw, dan ter-pin ke kiri di md+.
     */
    public function test_hero_text_capped_on_tablet(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('md:w-[60%]', $response->getContent());
        $this->assertStringContainsString('lg:w-1/2', $response->getContent());
        $this->assertStringContainsString('md:justify-start', $response->getContent());
        $this->assertStringContainsString('md:-right-10', $response->getContent());
    }
}
