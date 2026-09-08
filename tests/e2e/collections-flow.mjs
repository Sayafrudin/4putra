/**
 * E2E: halaman koleksi — zoom lightbox tidak raksasa + galeri varian.
 * Skenario:
 *  K1 Buka /collections → card Blue-eyed ada
 *  K2 Klik card induk → modal terbuka → section VARIANTS ada
 *  K3 Klik thumbnail varian (multi-foto) → lightbox terbuka, img bounding < viewport
 *  K4 Klik next → counter berubah (galeri jalan)
 *  K5 Escape menutup lightbox; Escape juga menutup modal
 */
import { chromium } from 'playwright-core';
import { spawn } from 'node:child_process';
import http from 'node:http';

const PORT = 8021;
const BASE = `http://127.0.0.1:${PORT}`;

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/collections`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                if (n <= 0) return reject(new Error(`Server tidak siap (${res.statusCode})`));
                setTimeout(() => tryOnce(n - 1), 1000);
            }).on('error', () => n <= 0 ? reject(new Error('Server tidak siap')) : setTimeout(() => tryOnce(n - 1), 1000));
        };
        tryOnce(retries);
    });
}

let server = null;
try {
    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
        stdio: 'ignore',
    });
    await waitForServer();

    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const page = await (await browser.newContext({ viewport: { width: 1280, height: 800 } })).newPage();

    // K1
    await page.goto(`${BASE}/collections`, { waitUntil: 'load' });
    const card = page.locator('button', { hasText: /Blue-?[eE]yed Cockatoo/i }).first();
    assert(await card.count() > 0, 'K1 card Blue-eyed Cockatoo ada di grid');

    if (await card.count()) {
        // K2: buka modal induk
        await card.click();
        const dialog = page.locator('[role="dialog"]');
        await dialog.waitFor({ state: 'visible', timeout: 5000 });
        assert(true, 'K2a modal detail terbuka setelah klik card');

        const variantsTitle = page.getByText(/VARIANTS|VARIAN/i).first();
        assert(await variantsTitle.count() > 0, 'K2b section VARIANTS ada di modal');

        const thumbs = dialog.locator('div.relative img');
        const n = await thumbs.count();
        assert(n > 0, `K2c thumbnail varian terrender (jumlah: ${n})`);

        if (n > 0) {
            // Cari varian multi-foto (badge +N): klik satu per satu sampai mode galeri aktif
            let tested = false;
            for (let i = 0; i < n && !tested; i++) {
                await thumbs.nth(i).click();
                const overlay = page.locator('.zoom-media-overlay');
                await overlay.waitFor({ state: 'visible', timeout: 5000 });
                await sleep(500);

                const counter = overlay.locator('.zoom-media-count');
                if (await counter.count()) {
                    tested = true;

                    // K3: lightbox img muat di viewport (tidak raksasa).
                    // Tunggu bounding box nyata (transisi scale + loading img selesai).
                    let box = null;
                    for (let w = 0; w < 20; w++) {
                        box = await overlay.locator('.zoom-media-img').boundingBox();
                        if (box && box.width > 0 && box.height > 0) break;
                        await sleep(250);
                    }
                    const vp = page.viewportSize();
                    assert(
                        box && box.width > 0 && box.width <= vp.width && box.height > 0 && box.height <= vp.height,
                        `K3 lightbox img ${Math.round(box?.width || 0)}x${Math.round(box?.height || 0)} muat di viewport ${vp.width}x${vp.height} (tidak raksasa)`
                    );

                    // K4: counter + next
                    const c1 = await counter.textContent();
                    await overlay.locator('.zoom-media-next').click();
                    await sleep(600);
                    const c2 = await counter.textContent();
                    assert(c1 !== c2, `K4 galeri next jalan: "${c1}" → "${c2}"`);

                    // K5: Escape menutup lightbox
                    await page.keyboard.press('Escape');
                    await sleep(500);
                    assert((await overlay.count()) === 0 || !(await overlay.isVisible()), 'K5a Escape menutup lightbox');
                } else {
                    await page.keyboard.press('Escape');
                    await sleep(500);
                }
            }
            assert(tested, 'K4 varian multi-foto ditemukan dan digaleri');

            // Escape menutup modal detail
            await page.keyboard.press('Escape');
            await sleep(600);
            assert(!(await page.locator('[role="dialog"]').isVisible()), 'K5b Escape menutup modal detail');
        }
    }

    // Performa: waktu render halaman koleksi
    await page.goto(`${BASE}/collections`, { waitUntil: 'load' });
    const dcl = await page.evaluate(() => {
        const nav = performance.getEntriesByType('navigation')[0];
        return Math.round(nav.domContentLoadedEventEnd - nav.startTime);
    });
    assert(dcl < 4000, `K6 render /collections = ${dcl}ms (< 4000ms)`);

    await browser.close();
} catch (err) {
    failures++;
    console.log(`FAIL  Exception: ${err.message}`);
} finally {
    if (server) server.kill();
}

console.log('='.repeat(50));
console.log(failures === 0 ? 'SEMUA E2E KOLEKSI LULUS' : `${failures} E2E GAGAL`);
process.exit(failures === 0 ? 0 : 1);
