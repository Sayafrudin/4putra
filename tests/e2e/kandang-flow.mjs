/**
 * E2E browser nyata: Kandang & penempatan burung (drag & drop).
 *  K1  Papan kandang dirender (blok + pool)
 *  K2  Tambah burung via modal (validasi kustom) → muncul di pool
 *  K3  Drag pool → kandang A-1 → toast sukses + SETELAH RELOAD server
 *      menunjukkan burung benar-benar di A-1 (bukan gimmick DOM saja)
 *  K4  Drag kandang → pool → server mengosongkan kandang
 *  K5  Hapus burung via modal konfirmasi → server kehilangan baris
 *
 * Jalankan: node tests/e2e/kandang-flow.mjs
 */
import { chromium } from 'playwright-core';
import { spawn, execSync } from 'node:child_process';
import http from 'node:http';

const PORT = 8021;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: 'e2e-kandang@4putra.test', password: 'E2eKandang123!' };
const BURUNG = 'E2E Kandang';

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(() => {
    console.log('WATCHDOG: 8 menit tercapai, proses dihentikan paksa');
    process.exit(1);
}, 8 * 60 * 1000).unref();

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/login`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                if (n <= 0) return reject(new Error(`Server tidak siap (terakhir: ${res.statusCode})`));
                setTimeout(() => tryOnce(n - 1), 1000);
            }).on('error', () => n <= 0 ? reject(new Error('Server tidak siap')) : setTimeout(() => tryOnce(n - 1), 1000));
        };
        tryOnce(retries);
    });
}

function tinker(code) {
    execSync(`php artisan tinker --execute="${code}"`, { stdio: 'ignore', timeout: 90000 });
}

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} App\\Models\\Burung::where('nama','like','${BURUNG}%')->delete(); echo 'bersih';"`,
        { stdio: 'ignore', timeout: 60000 }
    );
}

let server = null;
let browser = null;

function matikanServer() {
    if (!server) return;
    try { execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: 'ignore' }); }
    catch { try { server.kill(); } catch { /* sudah mati */ } }
    server = null;
}

try {
    cleanupDb();
    tinker(`App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Kandang','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';`);
    tinker(`if(App\\Models\\Kandang::count()===0){ (new Database\\Seeders\\KandangSeeder)->run(); } echo 'kandang siap';`);

    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
        stdio: 'ignore',
    });
    const status = await waitForServer();
    assert(status === 200, `Smoke HTTP /login = ${status}`);

    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // ---------- Login via form asli ----------
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    assert(true, 'K0 login via form → /admin');

    // ---------- K1: papan kandang dirender ----------
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    assert((await page.locator('h3:has-text("Blok A")').count()) > 0, 'K1a Blok A tersedia');
    assert((await page.locator('[data-kandang-label="A-1"]').count()) === 1, 'K1b kandang A-1 dirender');
    assert((await page.locator('#poolList').count()) === 1, 'K1c pool burung tersedia');

    // ---------- K2: tambah burung via modal ----------
    await page.click('text=+ Tambah Burung');
    await page.fill('#tambah_nama', BURUNG);
    await page.fill('#tambah_spesies', 'African Grey');
    await page.selectOption('#tambah_fase', 'anakan');
    await Promise.all([
        page.waitForNavigation({ waitUntil: 'load' }),
        page.click('#btnTambah'),
    ]).catch(() => { /* reload mungkin sudah selesai sebelum waitForNavigation mulai */ });
    const chip = page.locator(`#poolList .burung-chip:has-text("${BURUNG}")`);
    await chip.waitFor({ state: 'visible', timeout: 15000 });
    assert((await chip.count()) === 1, 'K2 burung baru muncul di pool (server-reload)');

    // ---------- K3: drag pool → kandang A-1 ----------
    await page.evaluate(() => {
        const chipEl = document.querySelector('#poolList .burung-chip');
        const cage = document.querySelector('[data-kandang-label="A-1"]');
        const dt = new DataTransfer();
        chipEl.dispatchEvent(new DragEvent('dragstart', { bubbles: true, cancelable: true, dataTransfer: dt }));
        cage.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
        cage.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
        chipEl.dispatchEvent(new DragEvent('dragend', { bubbles: true, dataTransfer: dt }));
    });
    await sleep(2500); // tunggu fetch POST selesai
    assert((await page.getByText(/ditempatkan di kandang A-1/).count()) > 0, 'K3a toast sukses penempatan');

    // BUKTI SERVER: reload → burung benar di A-1, pool berkurang
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    assert((await page.locator('[data-kandang-label="A-1"] .burung-chip:has-text("E2E Kandang")').count()) === 1,
        'K3b setelah reload, burung ada di A-1 (state server)');
    assert((await page.locator('[data-kandang-label="A-1"] .kandang-status:has-text("1 burung")').count()) === 1,
        'K3c status kandang A-1 = 1 burung');

    // ---------- K4: drag kandang → pool ----------
    await page.evaluate(() => {
        const chipEl = document.querySelector('[data-kandang-label="A-1"] .burung-chip');
        const pool = document.querySelector('#poolList');
        const dt = new DataTransfer();
        chipEl.dispatchEvent(new DragEvent('dragstart', { bubbles: true, cancelable: true, dataTransfer: dt }));
        pool.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
        pool.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
        chipEl.dispatchEvent(new DragEvent('dragend', { bubbles: true, dataTransfer: dt }));
    });
    await sleep(2500);
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    assert((await page.locator(`#poolList .burung-chip:has-text("${BURUNG}")`).count()) === 1,
        'K4 setelah reload, burung kembali di pool (kandang dikosongkan server-side)');

    // ---------- K5: hapus via modal konfirmasi ----------
    await page.click(`#poolList .burung-chip:has-text("${BURUNG}") .hapus-burung`);
    await page.click('#btnHapus');
    await sleep(2500);
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    assert((await page.locator(`.burung-chip:has-text("${BURUNG}")`).count()) === 0,
        'K5 burung terhapus dari server (tidak ada di papan)');

    // ---------- Sisi-sisi tampilan bebas error konsol ----------
    const errors = [];
    page.on('pageerror', (e) => errors.push(e.message));
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    assert(errors.length === 0, 'K6 tanpa error konsol di papan kandang');

    console.log('==================================================');
    if (failures > 0) { console.log(`GAGAL: ${failures} pemeriksaan`); process.exitCode = 1; }
    else console.log('SEMUA E2E KANDANG LULUS');
} catch (e) {
    console.log('ERROR:', e.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    matikanServer();
}
