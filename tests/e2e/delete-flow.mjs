/**
 * E2E khusus bug: tombol Hapus macet setelah delete pertama (admin-refresh.js).
 * Skenario persis laporan user: hapus 1 data uji -> hapus data kedua -> tombol harus aktif.
 * Verifikasi sisi server: baris DB benar-benar terhapus (bukan cuma hilang dari DOM).
 * Jalankan: node tests/e2e/delete-flow.mjs  (butuh php artisan serve di port 8000)
 */
import { chromium } from 'playwright-core';
import { execSync } from 'node:child_process';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const BASE = 'http://127.0.0.1:8000';
const ADMIN = { email: 'admin@4putra.com', password: 'password123' };
const N1 = 'E2E Hapus Uji 1';
const N2 = 'E2E Hapus Uji 2';

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}

function dbCount(name) {
    const out = execSync(
        `php artisan tinker --execute="echo App\\Models\\Collection::where('name','${name}')->count();"`,
        { encoding: 'utf8', timeout: 60000 }
    );
    return parseInt(out.trim(), 10);
}

function seed() {
    execSync(
        `php artisan tinker --execute="App\\Models\\Collection::firstOrCreate(['name'=>'${N1}'],['category'=>'Uji','sort_order'=>9999]); App\\Models\\Collection::firstOrCreate(['name'=>'${N2}'],['category'=>'Uji','sort_order'=>9998]); Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); echo 'seed ok';"`,
        { stdio: 'pipe', timeout: 60000 }
    );
}

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="App\\Models\\Collection::whereIn('name',['${N1}','${N2}'])->delete(); Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); echo 'bersih';"`,
        { stdio: 'ignore', timeout: 60000 }
    );
}

let browser = null;
try {
    cleanupDb();
    seed();
    assert(dbCount(N1) === 1 && dbCount(N2) === 1, 'Seed: 2 koleksi uji ada di DB');

    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const page = await browser.newPage();

    // Login via form asli
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    // Field login memakai readonly+onfocus (anti-autofill): klik dulu agar readonly hilang
    await page.click('#email');
    await page.fill('#email', ADMIN.email);
    await page.click('#password');
    await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL('**/admin**', { timeout: 15000 });

    // Performa: load halaman koleksi < 3 detik
    const navStart = Date.now();
    await page.goto(`${BASE}/admin/collections`, { waitUntil: 'domcontentloaded' });
    const navMs = Date.now() - navStart;
    assert(navMs < 3000, `Performa: /admin/collections load ${navMs}ms < 3000ms`);

    // Cache-buster admin-refresh.js ter-render dari server
    const jsVer = await page.evaluate(() => {
        const s = [...document.querySelectorAll('script[src]')].find((x) => x.src.includes('admin-refresh.js'));
        return s ? s.src : null;
    });
    assert(!!jsVer && jsVer.includes('?v='), `Cache-buster JS aktif: ${jsVer ? jsVer.split('/').pop() : 'tidak ditemukan'}`);

    async function clickHapus(name) {
        const row = page.locator('tr', { hasText: name }).first();
        await row.locator('button', { hasText: 'Hapus' }).first().click();
        const modal = page.locator('#modal-delete-collection');
        await modal.waitFor({ state: 'visible', timeout: 5000 });
        await modal.locator('#form-delete-collection button[type="submit"]').click();
    }

    async function waitRowGone(name) {
        // refreshAdminList menukar tbody; tunggu sampai baris benar-benar hilang dari DOM
        await page.waitForFunction(
            (n) => ![...document.querySelectorAll('tbody tr')].some((tr) => tr.textContent.includes(n)),
            name,
            { timeout: 10000 }
        );
    }

    // --- DELETE #1 ---
    await clickHapus(N1);
    await waitRowGone(N1);
    assert(true, `Delete #1: "${N1}" hilang dari DOM`);
    await sleep(300);
    assert(dbCount(N1) === 0, `Delete #1 server: "${N1}" terhapus dari DB`);

    // --- DELETE #2 (inti bug: tombol tidak boleh macet) ---
    const row2 = page.locator('tr', { hasText: N2 }).first();
    const btn2 = row2.locator('button', { hasText: 'Hapus' }).first();
    await btn2.waitFor({ state: 'visible', timeout: 5000 });
    const btnState = await btn2.evaluate((b) => ({ disabled: b.disabled, text: b.textContent.trim() }));
    assert(btnState.disabled === false, `Delete #2 tombol tidak disabled (state: disabled=${btnState.disabled}, "${btnState.text}")`);
    assert(btnState.text === 'Hapus', `Delete #2 tombol teks normal (bukan "Menghapus..."): "${btnState.text}"`);

    await clickHapus(N2);
    await waitRowGone(N2);
    assert(true, `Delete #2: "${N2}" hilang dari DOM`);
    await sleep(300);
    assert(dbCount(N2) === 0, `Delete #2 server: "${N2}" terhapus dari DB`);

} catch (e) {
    console.log('FAIL  Exception: ' + e.message);
    failures++;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    assert(dbCount(N1) === 0 && dbCount(N2) === 0, 'Cleanup: semua data uji dihapus dari DB');
    console.log(failures === 0 ? '\nSEMUA PASS' : `\n${failures} GAGAL`);
    process.exit(failures === 0 ? 0 : 1);
}
