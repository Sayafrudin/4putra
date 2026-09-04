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
     * Hero: di tablet burung in-flow mencegah tumpang tindih (teks flex-1
     * otomatis menyisakan kolom burung); di desktop burung absolute sehingga
     * teks wajib di-cap 50%. Teks ter-pin kiri di md+.
     */
    public function test_hero_text_capped_on_tablet(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('md:flex-1', $response->getContent());
        $this->assertStringContainsString('xl:w-1/2', $response->getContent());
        $this->assertStringContainsString('md:justify-start', $response->getContent());
        $this->assertStringNotContainsString('md:w-[60%]', $response->getContent());
    }

    /**
     * Arsitektur gambar hero per-breakpoint:
     * - Tablet (md-lg): kolom flex IN-FLOW (bukan absolute) -> items-center
     *   mensejajarkan burung tepat di tengah vertikal terhadap teks.
     * - Desktop (xl+): absolute ke SECTION (w-full) -> right-0 = flush tepi
     *   kanan viewport; anchor container max-w-7xl dilarang (kayu putus).
     * Selalu width-driven (rasio konten 0.609), tanpa cap tinggi (gap transparan).
     */
    public function test_hero_image_tablet_inflow_desktop_flush_right(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $this->assertStringContainsString('md:w-[42%]', $response->getContent());
        $this->assertStringContainsString('lg:w-[40%]', $response->getContent());
        $this->assertStringContainsString('md:shrink-0', $response->getContent());
        $this->assertStringContainsString('md:flex-1', $response->getContent());
        $this->assertStringContainsString('xl:w-1/2', $response->getContent());
        $this->assertStringContainsString('xl:absolute', $response->getContent());
        $this->assertStringContainsString('xl:right-0', $response->getContent());
        $this->assertStringContainsString('xl:top-12', $response->getContent());
        $this->assertStringContainsString('xl:w-[min(33vw,690px)]', $response->getContent());
        $this->assertStringNotContainsString('md:top-1/2', $response->getContent());
        $this->assertStringNotContainsString('-top-24', $response->getContent());
        $this->assertStringNotContainsString('-top-36', $response->getContent());
        $this->assertStringNotContainsString('md:-right-10', $response->getContent());
        $this->assertStringNotContainsString('50%-50vw', $response->getContent());
        $this->assertStringNotContainsString('h-[80%]', $response->getContent());
        $this->assertStringNotContainsString('h-[115%]', $response->getContent());
    }
}
