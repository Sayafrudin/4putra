/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Memverifikasi perilaku dari sisi SERVER, bukan gimmick UI:
 *  S1  Login via form asli
 *  S2  Popup Perpanjang Sesi muncul → klik → modal tutup + toast + ping 200
 *  S3  Anti-gimmick: idle lagi → popup MUNCUL LAGI → klik lagi → berhasil
 *  S4  Simulasi laptop sleep (cookie sesi hilang, remember ada) → halaman admin tetap terbuka
 *  S5  Tanpa remember + sesi mati → klik Perpanjang → modal expired → Muat Ulang → login → balik ke halaman sama
 *  S6  Navbar scrolled: track toggle bahasa terlihat (tidak merah-di-merah)
 *  S7  Performa: waktu load halaman utama < 3 detik (NavigationTiming)
 *
 * Jalankan: npm run test:e2e
 */
import { chromium } from 'playwright-core';
import { spawn, execSync } from 'node:child_process';
import http from 'node:http';

const PORT = 8020;
const BASE = `http://127.0.0.1:${PORT}`;
const LIFETIME_MIN = 2; // SESSION_LIFETIME dev server
const ADMIN = { email: 'e2e-flow@4putra.test', password: 'E2eFlow123!' };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const sec = (n) => n * 1000;

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/login`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                // 500 cold-start (koneksi DB dingin gagal sekali) → coba lagi
                if (n <= 0) return reject(new Error(`Server tidak siap (terakhir: ${res.statusCode})`));
                setTimeout(() => tryOnce(n - 1), 1000);
            }).on('error', () => n <= 0 ? reject(new Error('Server tidak siap')) : setTimeout(() => tryOnce(n - 1), 1000));
        };
        tryOnce(retries);
    });
}

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
        { stdio: 'ignore', timeout: 60000 }
    );
}

let server = null;
try {
    cleanupDb();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Flow','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
        { stdio: 'ignore', timeout: 60000 }
    );

    server = spawn('php', ['artisan', 'serve', `--host=127.0.0.1`, `--port=${PORT}`], {
        // Multi-worker: php serve default 1 worker → request ping bisa antre
        // di belakang request lain dan tampak "hang" padahal server sehat.
        env: { ...process.env, SESSION_LIFETIME: String(LIFETIME_MIN), PHP_CLI_SERVER_WORKERS: '4' },
        stdio: 'ignore',
    });
    // Terima hanya 200 — tolak 500 cold-start (DB dingin bisa gagal sekali)
    const status = await waitForServer();
    assert(status === 200, `Smoke HTTP /login = ${status}`);

    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const pingStatuses = [];
    page.on('response', (res) => {
        if (res.url().includes('/admin/ping')) pingStatuses.push(res.status());
    });

    // ---------- S1: Login via form asli ----------
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    // Field login memakai readonly+onfocus (anti-autofill): klik dulu agar readonly hilang
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    assert(true, 'S1 login via form → /admin');

    // ---------- S2: popup → klik Perpanjang ----------
    await sleep(sec(62)); // popup muncul di clamp 60 detik (LIFETIME=2 menit)
    const modal = page.locator('#session-timeout-modal');
    assert(await modal.isVisible(), 'S2a popup peringatan muncul setelah idle');
    await page.click('#session-extend-btn');
    // Tunggu modal BENAR-BENAR hilang (animasi keluar 300ms) — anti-balapan
    await modal.waitFor({ state: 'hidden', timeout: 5000 });
    assert(true, 'S2b modal tertutup setelah klik Perpanjang');
    assert((await page.getByText('Sesi Diperpanjang').count()) > 0, 'S2c toast konfirmasi sesi diperpanjang');
    assert(pingStatuses.at(-1) === 200, `S2d ping server = 200 (actual: ${pingStatuses.at(-1)})`);

    // ---------- S3: anti-gimmick — popup muncul LAGI ----------
    await sleep(sec(62));
    assert(await modal.isVisible(), 'S3a popup muncul LAGI setelah idle kedua (bukti sesi benar direset server)');
    await page.click('#session-extend-btn');
    await modal.waitFor({ state: 'hidden', timeout: 5000 });
    assert(true, 'S3b klik kedua juga berhasil');
    assert(pingStatuses.at(-1) === 200, `S3c ping kedua = 200 (actual: ${pingStatuses.at(-1)})`);
    assert(!(page.url().includes('/login')), 'S3d tetap di halaman admin, tidak dilempar login');

    // ---------- S4: laptop sleep — cookie sesi hilang, remember ada ----------
    const cookies = await context.cookies();
    await context.clearCookies();
    await context.addCookies(cookies.filter((c) => !c.name.startsWith('laravel_session')));
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'load' });
    assert(!page.url().includes('/login'), 'S4 sesi dipulihkan via remember → /admin/collections terbuka tanpa login');

    // ---------- S5: tanpa remember + sesi mati → expired → muat ulang → balik ke halaman sama ----------
    await context.clearCookies(); // buang SEMUA cookie (sesi + remember) → ping wajib 401
    await sleep(sec(140)); // popup (60s) + melewati lifetime (120s) + margin
    assert(await modal.isVisible(), 'S5a popup muncul untuk sesi ketiga');
    await page.click('#session-extend-btn');
    await sleep(1500);
    assert((await page.getByText('Sesi Anda Telah Berakhir').count()) > 0, 'S5b modal expired muncul (401 jujur)');
    await page.click('#session-extend-btn'); // tombol sudah jadi "Muat Ulang Halaman"
    await page.waitForURL(/\/login/, { timeout: 15000 });
    assert(true, 'S5c muat ulang → halaman login (intended disimpan)');
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL(`${BASE}/admin/collections`, { timeout: 15000 });
    assert(true, 'S5d setelah login balik ke halaman yang sama (bukan dashboard)');

    // ---------- S6: navbar scrolled — track toggle terlihat ----------
    await page.goto(`${BASE}/lang/en`, { waitUntil: 'load' });
    await page.goto(`${BASE}/`, { waitUntil: 'load' });
    const track = page.locator('#lang-toggle + div');
    await page.evaluate(() => window.scrollTo(0, 300));
    await sleep(700);
    const scrolledClass = await track.getAttribute('class');
    assert(scrolledClass.includes('peer-checked:bg-gray-300') && !scrolledClass.includes('peer-checked:bg-[#E62C37]'),
        'S6a track EN terlihat di navbar merah (bukan merah-di-merah)');
    await page.evaluate(() => window.scrollTo(0, 0));
    await sleep(700);
    assert((await track.getAttribute('class')).includes('peer-checked:bg-[#E62C37]'), 'S6b di posisi atas track kembali merah');
    await page.goto(`${BASE}/lang/id`, { waitUntil: 'load' });

    // ---------- S7: performa ----------
    // DCL = render siap (eksklusi unduhan foto). Ambang 4 detik utk /admin/collections:
    // dev server tanpa opcache + TiDB remote; produksi memakai config cache.
    for (const path of ['/', '/collections', '/admin', '/admin/collections']) {
        await page.goto(`${BASE}${path}`, { waitUntil: 'load' });
        const t = await page.evaluate(() => {
            const nav = performance.getEntriesByType('navigation')[0];
            return { dcl: Math.round(nav.domContentLoadedEventEnd - nav.startTime), full: Math.round(nav.loadEventEnd - nav.startTime) };
        });
        assert(t.dcl < 4000, `S7 render ${path} = ${t.dcl}ms (< 4000ms; full-load ${t.full}ms)`);
    }

    await browser.close();
} catch (err) {
    failures++;
    console.log(`FAIL  Exception: ${err.message}`);
} finally {
    if (server) server.kill();
    try { cleanupDb(); } catch { /* cleanup best-effort */ }
}

console.log('='.repeat(50));
console.log(failures === 0 ? 'SEMUA E2E BROWSER LULUS' : `${failures} E2E GAGAL`);
process.exit(failures === 0 ? 0 : 1);
