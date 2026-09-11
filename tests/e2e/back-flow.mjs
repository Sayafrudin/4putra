/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Skenario permanen regresi tombol back Android & scroll strip galeri:
 *  S1  Back Android dari drawer: drawer tertutup, aria-pressed reset, toggle tetap hidup
 *  S2  Restore overlay zoom: back+forward → overlay hilang, body scroll bebas
 *  S3  Wheel desktop: strip galeri ter-scroll horizontal, scrollbar thin di desktop / none di mobile
 *
 * Prasyarat: dev server Laravel sudah jalan di http://127.0.0.1:8000 (read-only, tanpa data uji).
 * Jalankan: node tests/e2e/back-flow.mjs
 */
import { chromium } from 'playwright-core';

const BASE = 'http://127.0.0.1:8000';

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const MOBILE = { viewport: { width: 390, height: 844 }, hasTouch: true, isMobile: true };

// Cari locator .media-strip pertama yang benar-benar overflow pada sebuah path (null jika tidak ada)
async function findStrip(page, path) {
    await page.goto(`${BASE}${path}`, { waitUntil: 'load' });
    const idx = await page.evaluate(() =>
        [...document.querySelectorAll('.media-strip')].findIndex((el) => el.scrollWidth > el.clientWidth)
    );
    return idx < 0 ? null : page.locator('.media-strip').nth(idx);
}

let browser = null;
try {
    browser = await chromium.launch({ channel: 'msedge', headless: true });

    // ---------- S1: back Android menutup drawer ----------
    const ctx = await browser.newContext(MOBILE);
    const page = await ctx.newPage();

    await page.goto(`${BASE}/collections`, { waitUntil: 'load' });
    await page.click('#hamburger-btn');
    await sleep(400); // transisi drawer 300ms
    assert(!(await page.getAttribute('#mobile-menu', 'class')).includes('invisible'), 'S1a drawer terbuka setelah klik hamburger');

    await page.click('#mobile-menu a[href="/facilities"]');
    await page.waitForURL(`${BASE}/facilities`, { timeout: 15000 });
    await page.goBack();
    await page.waitForURL(`${BASE}/collections`, { timeout: 15000 });
    await sleep(800); // Turbo restore + transisi selesai
    assert((await page.getAttribute('#mobile-menu', 'class')).includes('invisible'), 'S1b back Android → drawer tertutup');
    assert((await page.getAttribute('#hamburger-btn', 'aria-pressed')) === 'false', 'S1c hamburger aria-pressed = false');

    await page.click('#hamburger-btn');
    await sleep(400);
    assert(!(await page.getAttribute('#mobile-menu', 'class')).includes('invisible') &&
        (await page.getAttribute('#hamburger-btn', 'aria-pressed')) === 'true', 'S1d hamburger toggle hidup (buka lagi)');
    await page.click('#hamburger-btn');
    await sleep(400);
    assert((await page.getAttribute('#mobile-menu', 'class')).includes('invisible'), 'S1e hamburger toggle hidup (tutup lagi)');
    const overlaysS1 = await page.evaluate(() => document.querySelectorAll('.zoom-media-overlay').length);
    assert(overlaysS1 === 0, 'S1f tidak ada zoom-media-overlay tersisa');

    // ---------- S2: restore overlay zoom setelah back+forward ----------
    await page.goto(`${BASE}/`, { waitUntil: 'load' });
    await page.locator('a[href="/collections"]:visible').first().click(); // Turbo visit
    await page.waitForURL(`${BASE}/collections`, { timeout: 15000 });
    await page.locator('[onclick*="zoomMedia"]').first().click();
    await page.waitForSelector('.zoom-media-overlay', { timeout: 5000 });
    assert(true, 'S2a overlay zoom terbuka di /collections');

    await page.goBack();
    await page.waitForURL(`${BASE}/`, { timeout: 15000 });
    await page.goForward();
    await page.waitForURL(`${BASE}/collections`, { timeout: 15000 });
    await sleep(800); // Turbo restore + transisi selesai
    const overlaysS2 = await page.evaluate(() => document.querySelectorAll('.zoom-media-overlay').length);
    assert(overlaysS2 === 0, 'S2b back+forward → overlay zoom hilang');

    await page.evaluate(() => window.scrollTo(0, 600));
    await sleep(500); // scroll-behavior: smooth + emulasi mobile async
    const scrollY = await page.evaluate(() => window.scrollY);
    assert(scrollY === 600, `S2c body scroll bebas setelah forward (scrollY=${scrollY})`);
    await ctx.close();

    // ---------- S3: wheel desktop → strip galeri scroll horizontal ----------
    const desktop = await browser.newContext({ viewport: { width: 1024, height: 768 } });
    const dp = await desktop.newPage();
    let strip = await findStrip(dp, '/achievements');
    let stripPath = '/achievements';
    if (!strip) {
        stripPath = '/daily-activities';
        strip = await findStrip(dp, stripPath);
    }
    assert(!!strip, `S3a ada media-strip overflow di ${stripPath}`);
    await strip.scrollIntoViewIfNeeded();
    const before = await strip.evaluate((el) => el.scrollLeft);
    const box = await strip.boundingBox();
    const cdp = await desktop.newCDPSession(dp);
    await cdp.send('Input.dispatchMouseEvent', {
        type: 'mouseWheel',
        x: Math.round(box.x + box.width / 2),
        y: Math.round(box.y + box.height / 2),
        deltaX: 0,
        deltaY: 480,
    });
    await sleep(400);
    const after = await strip.evaluate((el) => el.scrollLeft);
    assert(after > before, `S3b wheel deltaY=480 → strip ter-scroll (${before} → ${after})`);
    const swDesktop = await strip.evaluate((el) => getComputedStyle(el).scrollbarWidth);
    assert(swDesktop === 'thin', `S3c scrollbar-width desktop = thin (actual: ${swDesktop})`);
    await desktop.close();

    // scrollbar-width di context mobile (pointer coarse)
    const mctx = await browser.newContext(MOBILE);
    const mp = await mctx.newPage();
    const mstrip = await findStrip(mp, stripPath);
    assert(!!mstrip, `S3d media-strip overflow di ${stripPath} (context mobile)`);
    const swMobile = await mstrip.evaluate((el) => getComputedStyle(el).scrollbarWidth);
    assert(swMobile === 'none', `S3e scrollbar-width mobile = none (actual: ${swMobile})`);
    await mctx.close();
} catch (err) {
    failures++;
    console.log(`FAIL  Exception: ${err.message}`);
} finally {
    if (browser) await browser.close();
}

console.log('='.repeat(50));
console.log(failures === 0 ? 'SEMUA E2E BROWSER LULUS' : `${failures} E2E GAGAL`);
process.exit(failures === 0 ? 0 : 1);
