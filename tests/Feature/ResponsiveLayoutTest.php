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
    }

    /**
     * Gambar macaw wajib width-driven (rasio konten 0.609) tanpa cap tinggi:
     * cap tinggi + object-contain membuat gambar fit-by-height dan muncul
     * gap transparan di kanan (kayu tidak menyatu ke tepi).
     */
    public function test_hero_image_width_driven_without_height_cap(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('md:w-[min(38vw,920px)]', $response->getContent());
        $this->assertStringContainsString('xl:w-[min(33vw,690px)]', $response->getContent());
        $this->assertStringNotContainsString('h-[80%]', $response->getContent());
        $this->assertStringNotContainsString('h-[115%]', $response->getContent());
    }

    /**
     * Posisi vertikal macaw: ter-center terhadap kolom teks pada tablet
     * (md+lg, termasuk iPad Pro 1024px) via top-1/2 -translate-y-1/2 pada
     * container relative; desktop (xl+) anchor atas dengan paritas look lama.
     * Offset -top-24/-top-36 milik PNG lama (berpadding) wajib hilang.
     */
    public function test_hero_image_vertically_centered_on_tablet(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('md:top-1/2', $response->getContent());
        $this->assertStringContainsString('md:-translate-y-1/2', $response->getContent());
        $this->assertStringContainsString('xl:top-12', $response->getContent());
        $this->assertStringNotContainsString('-top-24', $response->getContent());
        $this->assertStringNotContainsString('-top-36', $response->getContent());
        $this->assertStringNotContainsString('md:-right-10', $response->getContent());
    }
}
