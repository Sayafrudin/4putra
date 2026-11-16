import { chromium } from 'playwright-core';
import { spawn, execSync } from 'node:child_process';
import http from 'node:http';

const PORT = 8022;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: 'dbg-kandang@4putra.test', password: 'Dbg123456!' };

const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

function tinker(code) {
    execSync(`php artisan tinker --execute="${code}"`, { stdio: 'pipe', timeout: 90000 });
}
function cleanupDb() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){$u->delete();} App\\Models\\Burung::where('nama','like','DBG Burung%')->delete(); echo 'bersih';"`,
        { stdio: 'pipe', timeout: 60000 }
    );
}

let server = null;
try {
    cleanupDb();
    tinker(`App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'Debug','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); App\\Models\\Burung::create(['nama'=>'DBG Burung A','spesies'=>'Macaw','fase'=>'anakan','gender'=>'jantan','kandang_id'=>App\\Models\\Kandang::where('blok','A')->where('nomor',1)->first()->id]); App\\Models\\Burung::create(['nama'=>'DBG Burung B','spesies'=>'Macaw','fase'=>'dewasa']); echo 'data siap';`);

    server = spawn('php', ['artisan', 'serve', '--host=127.0.0.1', `--port=${PORT}`], { env: { ...process.env, PHP_CLI_SERVER_WORKERS: '4' }, stdio: 'ignore' });
    for (let i = 0; i < 30; i++) {
        try { await new Promise((res, rej) => http.get(`${BASE}/login`, (r) => { r.resume(); r.statusCode === 200 ? res() : rej(); }).on('error', rej)); break; } catch { await sleep(1000); }
    }

    const browser = await chromium.launch({ channel: 'msedge', headless: true });
    const page = await (await browser.newContext()).newPage();
    await page.goto(`${BASE}/login`, { waitUntil: 'load' });
    await page.click('#email'); await page.fill('#email', ADMIN.email);
    await page.click('#password'); await page.fill('#password', ADMIN.password);
    await page.click('button[type=submit]');
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });

    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });

    // Dump kartu A-1 persis seperti yang dirender server
    const html = await page.evaluate(() => {
        const el = document.querySelector('[data-kandang-label="A-1"]');
        return el ? el.outerHTML : 'TIDAK ADA A-1';
    });
    console.log('=== A-1 HTML ===');
    console.log(html.slice(0, 1500));
    console.log('=== STATUS TEXTS ===');
    const stats = await page.evaluate(() =>
        [...document.querySelectorAll('.kandang-status')].slice(0, 8).map((s) => s.textContent.trim())
    );
    console.log(JSON.stringify(stats));
    console.log('=== POOL COUNT ===');
    console.log(await page.evaluate(() => document.querySelector('[data-pool-count]')?.textContent));

    // Drag DBG Burung B (pool) -> A-1, tunggu toast, lalu reload dan dump ulang
    await page.evaluate(() => {
        const chipEl = [...document.querySelectorAll('.burung-chip')].find((c) => c.textContent.includes('DBG Burung B'));
        const cage = document.querySelector('[data-kandang-label="A-1"]');
        const dt = new DataTransfer();
        chipEl.dispatchEvent(new DragEvent('dragstart', { bubbles: true, cancelable: true, dataTransfer: dt }));
        cage.dispatchEvent(new DragEvent('dragover', { bubbles: true, cancelable: true, dataTransfer: dt }));
        cage.dispatchEvent(new DragEvent('drop', { bubbles: true, cancelable: true, dataTransfer: dt }));
        chipEl.dispatchEvent(new DragEvent('dragend', { bubbles: true, dataTransfer: dt }));
    });
    await page.getByText(/ditempatkan di kandang/).first().waitFor({ state: 'visible', timeout: 20000 }).catch(() => console.log('TOAST TIDAK MUNCUL'));
    await sleep(1000);
    await page.goto(`${BASE}/admin/kandang`, { waitUntil: 'load' });
    const html2 = await page.evaluate(() => {
        const el = document.querySelector('[data-kandang-label="A-1"]');
        return el ? el.outerHTML : 'TIDAK ADA A-1';
    });
    console.log('=== A-1 SETELAH DRAG + RELOAD ===');
    console.log(html2.slice(0, 1400));
    const dbCheck = execSync(
        `php artisan tinker --execute="echo App\\Models\\Burung::where('nama','DBG Burung B')->first()?->kandang_id ?? 'null';"`,
        { encoding: 'utf8', timeout: 60000 }
    );
    console.log('=== DB kandang_id DBG B ===', dbCheck.trim());

    await browser.close();
} catch (e) {
    console.log('ERROR:', e.message);
} finally {
    if (server) { try { execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: 'ignore' }); } catch {} }
    cleanupDb();
}
