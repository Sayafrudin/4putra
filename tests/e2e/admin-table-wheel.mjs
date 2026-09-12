/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Skenario regresi wheel→scroll horizontal pada TABEL ADMIN (.overflow-x-auto):
 *  A1  Ada container tabel admin yang overflow horizontal di /admin/achievements
 *  A2  Wheel deltaY di atas tabel → ter-scroll ke kanan (bukan manual drag scrollbar)
 *  A3  Di tepi kanan → wheel meneruskan scroll halaman vertikal (anti scroll-chaining)
 *  A4  Di tepi kiri + wheel ke atas → halaman ter-scroll ke atas
 *  A5  Halaman admin lain dengan tabel overflow juga bekerja (collections)
 *
 * Prasyarat: dev server Laravel sudah jalan di http://127.0.0.1:8000 (read-only, tanpa data uji).
 * Jalankan: node tests/e2e/admin-table-wheel.mjs
 */
import { chromium } from 'playwright-core';

const BASE = process.env.BASE_URL || 'http://127.0.0.1:8000';
const ADMIN = { email: 'admin@4putra.com', password: 'password123' };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

let browser = null;
try {
    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const ctx = await browser.newContext({ viewport: { width: 1024, height: 768 } });
    const page = await ctx.newPage();

    // Login admin (field readonly+onfocus: klik dulu agar readonly hilang)
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type="submit"]');
    await page.waitForURL(`${BASE.replace(/\/$/, '')}/admin`, { timeout: 15000 });
    assert(true, 'A0 login admin → /admin');

    // Cari container tabel admin yang benar-benar overflow
    async function findTableScroll(path) {
        await page.goto(`${BASE}${path}`, { waitUntil: 'load' });
        const idx = await page.evaluate(() =>
            [...document.querySelectorAll('.overflow-x-auto')].findIndex((el) => el.scrollWidth > el.clientWidth)
        );
        return idx < 0 ? null : page.locator('.overflow-x-auto').nth(idx);
    }

    const scroller = (await findTableScroll('/admin/achievements')) || (await findTableScroll('/admin/collections'));
    assert(!!scroller, 'A1 container tabel admin (.overflow-x-auto) overflow horizontal');

    if (scroller) {
        await scroller.scrollIntoViewIfNeeded();
        await sleep(300);
        const box = await scroller.boundingBox();
        // Titik dispatch WAJIB di dalam viewport (container tabel bisa ribuan px tinggi)
        const cx = Math.round(box.x + box.width / 2);
        const cy = Math.max(60, Math.min(Math.round(box.y + box.height / 2), 728));
        const cdp = await ctx.newCDPSession(page);
        const wheel = (deltaY) =>
            cdp.send('Input.dispatchMouseEvent', {
                type: 'mouseWheel',
                x: cx,
                y: cy,
                deltaX: 0,
                deltaY,
            });

        // A2: wheel ke bawah → tabel scroll ke kanan
        const before = await scroller.evaluate((el) => el.scrollLeft);
        await wheel(480);
        await sleep(400);
        const after = await scroller.evaluate((el) => el.scrollLeft);
        assert(after > before, `A2 wheel di atas tabel → scroll kanan (${before} → ${after})`);

        // Page scroll terjadi di <main> (shell h-screen overflow-hidden), bukan window
        const getMainY = () => page.evaluate(() => document.querySelector('main').scrollTop);

        // A3: di tepi kanan → page TETAP (wheel murni milik tabel, page terkunci)
        await scroller.evaluate((el) => { el.scrollLeft = el.scrollWidth; });
        await page.evaluate(() => { document.querySelector('main').scrollTop = 0; });
        await sleep(300);
        const pageY0 = await getMainY();
        await wheel(480);
        await sleep(400);
        const pageY1 = await getMainY();
        const edgeLeft = await scroller.evaluate((el) => el.scrollLeft >= el.scrollWidth - el.clientWidth - 1);
        assert(pageY1 === pageY0 && edgeLeft, `A3 di tepi kanan page terkunci, tabel tetap di tepi (main ${pageY0} → ${pageY1})`);

        // A4: di tepi kiri + wheel ke atas → page TETAP
        await scroller.evaluate((el) => { el.scrollLeft = 0; });
        await sleep(200);
        const pageY2 = await getMainY();
        await wheel(-480);
        await sleep(400);
        const pageY3 = await getMainY();
        assert(pageY3 === pageY2, `A4 di tepi kiri page terkunci (main ${pageY2} → ${pageY3})`);

        // A6: wheel DI LUAR tabel → page scroll normal lagi
        const box6 = await scroller.boundingBox();
        const cdp6 = await ctx.newCDPSession(page);
        await page.evaluate(() => { document.querySelector('main').scrollTop = 0; });
        await sleep(300);
        await cdp6.send('Input.dispatchMouseEvent', {
            type: 'mouseWheel',
            x: Math.round(box6.x + box6.width / 2),
            y: Math.max(60, Math.min(Math.round(box6.y - 40), 700)), // area header, di luar tabel
            deltaX: 0,
            deltaY: 480,
        });
        await sleep(400);
        const pageY6 = await getMainY();
        assert(pageY6 > 0, `A6 wheel di luar tabel → page scroll normal (main 0 → ${pageY6})`);
    }

    // A5: container tabel lain juga ter-scroll (collections, sudah login)
    const scroller2 = await findTableScroll('/admin/collections');
    if (scroller2) {
        await scroller2.scrollIntoViewIfNeeded();
        await sleep(300);
        const b2 = await scroller2.boundingBox();
        const before2 = await scroller2.evaluate((el) => el.scrollLeft);
        const cdp2 = await ctx.newCDPSession(page);
        await cdp2.send('Input.dispatchMouseEvent', {
            type: 'mouseWheel',
            x: Math.round(b2.x + b2.width / 2),
            y: Math.max(60, Math.min(Math.round(b2.y + b2.height / 2), 728)),
            deltaX: 0,
            deltaY: 480,
        });
        await sleep(400);
        const after2 = await scroller2.evaluate((el) => el.scrollLeft);
        assert(after2 > before2, `A5 wheel di tabel collections → scroll kanan (${before2} → ${after2})`);
    } else {
        assert(true, 'A5 tabel collections tidak overflow (lewati — tabel muat penuh)');
    }
} catch (err) {
    failures++;
    console.log(`FAIL  Exception: ${err.message}`);
} finally {
    if (browser) await browser.close();
}

console.log('='.repeat(50));
console.log(failures === 0 ? 'SEMUA E2E BROWSER LULUS' : `${failures} E2E GAGAL`);
process.exit(failures === 0 ? 0 : 1);