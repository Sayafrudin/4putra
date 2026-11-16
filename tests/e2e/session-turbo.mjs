/**
 * E2E: popup "Sesi Akan Berakhir" × navigasi sidebar Turbo.
 * Deterministik — popup dipicu via hook debug window.sessionTimeout (bukan idle 60 dtk):
 *  T1  popup muncul via hook
 *  T2  navigasi Turbo (Turbo.visit) → popup tertutup rapi + countdown tak lanjut (state reset)
 *  T3  popup MUNCUL LAGI via hook (bukti modal dibangun ulang di body baru, bukan referensi basi)
 *  T4  klik Perpanjang Sesi → ping 200 + modal tertutup + toast
 *
 * Jalur timer asli (idle clamp 60 dtk) sudah dicakup session-flow S2/S3.
 * Jalankan: node tests/e2e/session-turbo.mjs
 */
import { chromium } from 'playwright-core';
import { spawn, execSync } from 'node:child_process';
import http from 'node:http';

const PORT = 8026;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: 'e2e-spa@4putra.test', password: 'E2eSpa123!' };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(() => {
    console.log('WATCHDOG: melewati 8 menit, proses dihentikan paksa');
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

let server = null;
let browser = null;

function matikanServer() {
    if (!server) return;
    try { execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: 'ignore' }); }
    catch { try { server.kill(); } catch { /* sudah mati */ } }
    server = null;
}

const TINKER_SEED = `App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Spa','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'seed oke';`;
const TINKER_CLEAN = `$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';`;

try {
    execSync(`php artisan tinker --execute="${TINKER_CLEAN}"`, { stdio: 'ignore', timeout: 60000 });
    execSync(`php artisan tinker --execute="${TINKER_SEED}"`, { stdio: 'ignore', timeout: 60000 });

    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], {
        env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' },
        stdio: 'ignore',
    });
    const status = await waitForServer();
    assert(status === 200, `Smoke HTTP /login = ${status}`);

    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const pingStatuses = [];
    page.on('response', (res) => {
        if (res.url().includes('/admin/ping')) pingStatuses.push(res.status());
    });

    // ---------- Login ----------
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    assert(true, 'Login via form → /admin');

    const modal = page.locator('#session-timeout-modal');

    // ---------- T1: popup muncul via hook debug (deterministik) ----------
    await page.evaluate(() => window.sessionTimeout.showWarning());
    await modal.waitFor({ state: 'visible', timeout: 5000 });
    assert(true, 'T1 popup tampil via hook showWarning()');

    // ---------- T2: navigasi Turbo → popup tertutup rapi ----------
    await page.evaluate(() => window.Turbo.visit('/admin/kandang'));
    await page.waitForSelector('h2:has-text("Kandang")', { timeout: 15000 });
    const modalTerputus = await page.evaluate(() => {
        const m = document.getElementById('session-timeout-modal');
        return !m || !m.isConnected;
    });
    assert(modalTerputus, 'T2a popup tidak ada di body baru (tertutup rapi saat swap)');
    await page.evaluate(() => window.sessionTimeout.showWarning());
    await modal.waitFor({ state: 'visible', timeout: 5000 });
    assert(true, 'T2b popup MUNCUL LAGI di body baru — state direset, modal dibangun ulang');

    // ---------- T3: Perpanjang Sesi tetap berfungsi ----------
    await page.click('#session-extend-btn');
    await modal.waitFor({ state: 'hidden', timeout: 5000 });
    assert((await page.getByText('Sesi Diperpanjang').count()) > 0, 'T3a toast konfirmasi sesi diperpanjang');
    assert(pingStatuses.at(-1) === 200, `T3b ping server = 200 (actual: ${pingStatuses.at(-1)})`);
    assert(!page.url().includes('/login'), 'T3c tetap di admin, tidak dilempar login');
} catch (err) {
    console.log('FAIL  tak terduga:', err.message);
    failures++;
} finally {
    try { execSync(`php artisan tinker --execute="${TINKER_CLEAN}"`, { stdio: 'ignore', timeout: 60000 }); } catch { /* best effort */ }
    if (browser) await browser.close();
    matikanServer();
}

console.log(failures === 0 ? '\nSEMUA LULUS' : `\n${failures} GAGAL`);
process.exit(failures === 0 ? 0 : 1);
