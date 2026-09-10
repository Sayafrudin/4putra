/**
 * E2E UI modal koleksi publik: heading PHOTOS/VARIANTS besar + center,
 * garis pembatas antar section, nama varian terbaca.
 * Jalankan: node tests/e2e/public-modal.mjs  (butuh php artisan serve di port 8000)
 */
import { chromium } from 'playwright-core';
import { execSync } from 'node:child_process';

const BASE = 'http://127.0.0.1:8000';
const PARENT = 'E2E Publik Induk';
const V1 = 'E2E Publik Varian 1';
const V2 = 'E2E Publik Varian 2';

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}

function tinker(code) {
    return execSync(`php artisan tinker --execute="${code}"`, { encoding: 'utf8', timeout: 60000 }).trim();
}
function cleanupDb() {
    execSync(
        `php artisan tinker --execute="App\\Models\\Collection::whereIn('name',['${PARENT}','${V1}','${V2}'])->delete(); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); echo 'bersih';"`,
        { stdio: 'ignore', timeout: 60000 }
    );
}
function seed() {
    tinker(
        `$p = App\\Models\\Collection::firstOrCreate(['name'=>'${PARENT}'],` +
        `['category'=>'Uji E2E Publik','sort_order'=>9998,'scientific_name'=>'Cacatua Uji','images'=>['https://res.cloudinary.com/demo/image/upload/u1.jpg','https://res.cloudinary.com/demo/image/upload/u2.jpg']]); ` +
        `App\\Models\\Collection::firstOrCreate(['name'=>'${V1}'],['category'=>'Uji E2E Publik','parent_id'=>$p->id,'scientific_name'=>'Eolophus Uji','image_path'=>'https://res.cloudinary.com/demo/image/upload/v1.jpg']); ` +
        `App\\Models\\Collection::firstOrCreate(['name'=>'${V2}'],['category'=>'Uji E2E Publik','parent_id'=>$p->id,'scientific_name'=>'Eolophus Uji','image_path'=>'https://res.cloudinary.com/demo/image/upload/v2.jpg']); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); echo 'seed ok';`
    );
}

let browser = null;
try {
    cleanupDb();
    seed();

    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const page = await browser.newPage({ viewport: { width: 1280, height: 900 } });

    // Paksa locale ID agar teks heading deterministik ('Foto', 'Varian')
    await page.goto(`${BASE}/lang/id`, { waitUntil: 'load' });

    // Performa: load halaman koleksi < 3 detik
    const navStart = Date.now();
    await page.goto(`${BASE}/collections`, { waitUntil: 'domcontentloaded' });
    const navMs = Date.now() - navStart;
    assert(navMs < 3000, `Performa: /collections load ${navMs}ms < 3000ms`);

    // Klik card induk (button dialog dengan aria-label memuat nama induk)
    const card = page.locator('button[aria-haspopup="dialog"]', { hasText: PARENT }).first();
    await card.waitFor({ state: 'visible', timeout: 10000 });
    await card.click();

    const dialog = page.locator('div[role="dialog"]').first();
    await dialog.waitFor({ state: 'visible', timeout: 5000 });

    // Heading PHOTOS: besar (>=18px), putih, center
    const h4Foto = dialog.locator('h4', { hasText: 'Foto' }).first();
    await h4Foto.waitFor({ state: 'visible', timeout: 5000 });
    const styleFoto = await h4Foto.evaluate((el) => {
        const s = getComputedStyle(el);
        return { size: parseFloat(s.fontSize), color: s.color, align: s.textAlign };
    });
    assert(styleFoto.size >= 18, `Heading FOTO besar (fontSize=${styleFoto.size}px >= 18px)`);
    assert(styleFoto.align === 'center', `Heading FOTO center (textAlign=${styleFoto.align})`);
    assert(styleFoto.color.includes('255'), `Heading FOTO kontras putih (color=${styleFoto.color})`);

    // Heading VARIANTS: besar, putih, center
    const h4Var = dialog.locator('h4', { hasText: 'Varian' }).first();
    await h4Var.waitFor({ state: 'visible', timeout: 5000 });
    const styleVar = await h4Var.evaluate((el) => {
        const s = getComputedStyle(el);
        return { size: parseFloat(s.fontSize), color: s.color, align: s.textAlign };
    });
    assert(styleVar.size >= 18, `Heading VARIAN besar (fontSize=${styleVar.size}px >= 18px)`);
    assert(styleVar.align === 'center', `Heading VARIAN center (textAlign=${styleVar.align})`);

    // Garis pembatas: wrapper section varian punya border-top 1px + padding atas
    const sep = await h4Var.evaluate((el) => {
        const s = getComputedStyle(el.parentElement);
        return { border: parseFloat(s.borderTopWidth), pad: parseFloat(s.paddingTop) };
    });
    assert(sep.border === 1, `Garis pembatas tampil (borderTopWidth=${sep.border}px)`);
    assert(sep.pad >= 32, `Jarak pembatas cukup (paddingTop=${sep.pad}px >= 32px)`);

    // Overlay nama varian gaya card koleksi: kotak gelap, nama putih bold, ilmiah merah uppercase
    const pNama = dialog.locator('p', { hasText: V1 }).first();
    await pNama.waitFor({ state: 'visible', timeout: 5000 });
    const ovVar = await pNama.evaluate((el) => {
        const s = getComputedStyle(el);
        const box = getComputedStyle(el.parentElement);
        const sci = el.parentElement.querySelectorAll('p')[1];
        const ss = getComputedStyle(sci);
        return {
            color: s.color, bold: s.fontWeight, box: box.backgroundColor,
            sciColor: ss.color, sciUpper: ss.textTransform,
        };
    });
    assert(ovVar.bold === '700' && ovVar.color === 'rgb(255, 255, 255)', `Nama varian: putih bold (${ovVar.color}, w=${ovVar.bold})`);
    assert(ovVar.box.includes('0.85'), `Overlay gelap ala card koleksi (bg=${ovVar.box})`);
    assert(ovVar.sciUpper === 'uppercase', `Ilmiah varian uppercase (${ovVar.sciUpper})`);
    assert(/oklch\(0\.704|rgb\(248, 113, 113\)/.test(ovVar.sciColor), `Ilmiah varian merah (${ovVar.sciColor})`);

    // Foto galeri TANPA overlay nama (nama sudah di header modal) — hanya varian yang ber-overlay
    const pFotoCount = await dialog.locator('p', { hasText: PARENT }).count();
    assert(pFotoCount === 0, `Foto galeri tanpa overlay nama`);

    // Escape menutup modal (overflow pulih)
    await page.keyboard.press('Escape');
    await dialog.waitFor({ state: 'hidden', timeout: 5000 });
    const overflow = await page.evaluate(() => document.documentElement.style.overflow);
    assert(overflow === '', 'Escape menutup modal, scroll dipulihkan');
} catch (e) {
    console.log('FAIL  Exception: ' + e.message);
    failures++;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    console.log(failures === 0 ? '\nSEMUA PASS' : `\n${failures} GAGAL`);
    process.exit(failures === 0 ? 0 : 1);
}