/**
 * E2E khusus bug: tidak bisa menambah varian kedua pada koleksi yang sama
 * ("Koleksi yang sudah memiliki varian tidak dapat dipilih sebagai induk").
 * Alur nyata: klik "+ Varian" -> modal -> nama + foto -> Simpan (dua kali).
 * Verifikasi sisi server: 2 baris varian di DB dengan parent_id sama + badge "2 Varian".
 * Jalankan: node tests/e2e/variant-flow.mjs  (butuh php artisan serve di port 8000)
 */
import { chromium } from 'playwright-core';
import { execSync } from 'node:child_process';

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const BASE = 'http://127.0.0.1:8000';
const ADMIN = { email: 'admin@4putra.com', password: 'password123' };
const PARENT = 'E2E Induk Varian Uji';
const V1 = 'E2E Varian Uji 1';
const V2 = 'E2E Varian Uji 2';

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? 'PASS' : 'FAIL'}  ${label}`);
    if (!ok) failures++;
}

function tinker(code) {
    return execSync(`php artisan tinker --execute="${code}"`, { encoding: 'utf8', timeout: 60000 }).trim();
}
function dbCount(name) {
    const out = tinker(`echo App\\Models\\Collection::where('name','${name}')->count();`);
    return parseInt(out.trim(), 10);
}
function seed() {
    const id = tinker(
        `$c = App\\Models\\Collection::firstOrCreate(['name'=>'${PARENT}'],['category'=>'Uji','sort_order'=>9999]); ` +
        `echo $c->id;`
    );
    tinker(
        `Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); echo 'ok';`
    );
    return id;
}
function cleanupDb() {
    execSync(
        `php artisan tinker --execute="App\\Models\\Collection::whereIn('name',['${PARENT}','${V1}','${V2}'])->delete(); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('admin.collections'); ` +
        `Illuminate\\Support\\Facades\\Cache::forget('public.collections.v2'); echo 'bersih';"`,
        { stdio: 'ignore', timeout: 60000 }
    );
}

async function addVariantViaModal(page, parentName, variantName) {
    // Klik "+ Varian" di baris induk -> modal create terbuka dengan induk terpilih
    const row = page.locator('tr', { hasText: parentName }).first();
    await row.locator('button', { hasText: 'Varian' }).first().click();
    const info = page.locator('#create-col-parent-info');
    await info.waitFor({ state: 'visible', timeout: 5000 });
    const infoText = await info.textContent();
    assert(infoText.includes(parentName), `Modal varian terbuka: "${infoText.trim()}"`);

    await page.fill('#create-col-name', variantName);

    // Foto via Dropzone: canvas 1x1 jadi JPEG, masuk antrean (tanpa auto-upload)
    await page.evaluate(async () => {
        const blob = await new Promise((res) => document.createElement('canvas').toBlob(res, 'image/jpeg'));
        const file = new File([blob], 'uji.jpg', { type: 'image/jpeg' });
        Dropzone.forElement('#dz-collection-create').addFile(file);
    });
    const queued = await page.evaluate(() => Dropzone.forElement('#dz-collection-create').getQueuedFiles().length);
    assert(queued === 1, `Foto masuk antrean Dropzone (${queued} file)`);

    // Klik Simpan sungguhan -> upload Cloudinary -> POST store
    await page.click('#submit-create-collection-btn');
    // refreshAdminList menukar tbody hanya saat store sukses
    await page.waitForFunction(
        (n) => [...document.querySelectorAll('tbody tr')].some((tr) => tr.textContent.includes(n)),
        variantName,
        { timeout: 30000 }
    );
    assert(true, `Simpan varian "${variantName}" sukses (baris muncul via refresh daftar)`);
}

let browser = null;
try {
    cleanupDb();
    const parentId = seed();
    assert(dbCount(PARENT) === 1, 'Seed: 1 koleksi induk ada di DB');
    assert(parentId.trim() !== '', `Seed: id induk=${parentId}`);

    browser = await chromium.launch({ channel: 'msedge', headless: true });
    const page = await browser.newPage();

    // Login via form asli
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
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

    // --- VARIAN #1 ---
    await addVariantViaModal(page, PARENT, V1);
    await sleep(300);
    assert(dbCount(V1) === 1, `Varian #1 server: "${V1}" tersimpan di DB`);

    // --- VARIAN #2 (inti bug: sebelumnya ditolak 422) ---
    await addVariantViaModal(page, PARENT, V2);
    await sleep(300);
    assert(dbCount(V2) === 1, `Varian #2 server: "${V2}" tersimpan di DB`);

    // Server: tepat 2 varian di bawah induk yang sama, kedalaman tetap 1 level
    const nVariants = tinker(`echo App\\Models\\Collection::where('parent_id','${parentId.trim()}')->count();`);
    assert(parseInt(nVariants.trim(), 10) === 2, `Server: 2 varian dengan parent_id sama (dapat ${nVariants.trim()})`);

    // Admin DOM: badge jumlah varian ter-update
    await page.waitForFunction(
        (n) => [...document.querySelectorAll('tbody tr')].some((tr) => tr.textContent.includes(n) && tr.textContent.includes('2 Varian')),
        PARENT,
        { timeout: 10000 }
    );
    assert(true, 'Admin UI: badge "2 Varian" tampil di baris induk');

    // --- CLEANUP via UI asli (juga menghapus aset Cloudinary) ---
    for (const nama of [V2, V1, PARENT]) {
        const row = page.locator('tr', { hasText: nama }).first();
        await row.locator('button', { hasText: 'Hapus' }).first().click();
        const modal = page.locator('#modal-delete-collection');
        await modal.waitFor({ state: 'visible', timeout: 5000 });
        await modal.locator('#form-delete-collection button[type="submit"]').click();
        await page.waitForFunction(
            (n) => ![...document.querySelectorAll('tbody tr')].some((tr) => tr.textContent.includes(n)),
            nama,
            { timeout: 10000 }
        );
        await sleep(300);
    }
    assert(true, 'Cleanup UI: 3 baris uji dihapus via tombol Hapus asli');
} catch (e) {
    console.log('FAIL  Exception: ' + e.message);
    failures++;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    assert(dbCount(PARENT) === 0 && dbCount(V1) === 0 && dbCount(V2) === 0, 'Cleanup DB: semua data uji terhapus');
    console.log(failures === 0 ? '\nSEMUA PASS' : `\n${failures} GAGAL`);
    process.exit(failures === 0 ? 0 : 1);
}