/**
 * E2E alur sesi ala MyBCA (lifetime diperpendek via env SESSION_LIFETIME=2):
 * idle ±1 menit → popup "Perpanjang Sesi" → klik perpanjang = sesi lanjut;
 * idle lagi tanpa respons → auto-logout ke halaman login.
 * Jalankan: node tests/e2e/session-idle-flow.mjs  (durasi ±5 menit)
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";

const PORT = 8031;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-idle@4putra.test", password: "E2eIdle123!" };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(
    () => {
        console.log("WATCHDOG: 8 menit");
        process.exit(1);
    },
    8 * 60 * 1000,
).unref();

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/login`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                if (n <= 0)
                    return reject(
                        new Error(`Server tidak siap (${res.statusCode})`),
                    );
                setTimeout(() => tryOnce(n - 1), 1000);
            }).on("error", () =>
                n <= 0
                    ? reject(new Error("Server tidak siap"))
                    : setTimeout(() => tryOnce(n - 1), 1000),
            );
        };
        tryOnce(retries);
    });
}

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
        { stdio: "ignore", timeout: 60000 },
    );
}

let server = null;
let browser = null;

try {
    cleanupDb();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Idle','username'=>'e2e-idle','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
        { stdio: "ignore", timeout: 60000 },
    );

    // SESSION_LIFETIME=2 menit: popup ~60 detik idle, auto-logout ~120 detik idle
    server = spawn(
        "php",
        ["artisan", "serve", "--host=127.0.0.1", `--port=${PORT}`],
        {
            env: {
                ...process.env,
                PHP_CLI_SERVER_WORKERS: "4",
                SESSION_LIFETIME: "2",
            },
            stdio: "ignore",
        },
    );
    await waitForServer();

    browser = await chromium.launch({ channel: "msedge", headless: true });
    const context = await browser.newContext({
        viewport: { width: 1440, height: 900 },
    });
    const page = await context.newPage();
    const pageErrors = [];
    page.on("pageerror", (e) => pageErrors.push(e.message));

    await page.goto(`${BASE}/login`, { waitUntil: "load" });
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });

    // Tanpa remember-me: tidak ada cookie remember_web_*
    const adaRemember = (await context.cookies()).some((c) =>
        c.name.startsWith("remember_web_"),
    );
    assert(!adaRemember, "Login tanpa cookie remember-me");

    // Idle → popup perpanjang (maksimal tunggu 100 detik)
    const popup = await Promise.race([
        page.waitForSelector("#session-timeout-modal", {
            state: "visible",
            timeout: 100000,
        }),
        sleep(100000).then(() => null),
    ]);
    assert(!!popup, 'Popup "Sesi Akan Berakhir" muncul saat idle');

    // Klik Perpanjang Sesi → modal tutup + toast sukses
    await page.click("#session-extend-btn");
    const toast = await Promise.race([
        page.waitForSelector("text=Sesi Diperpanjang", { timeout: 10000 }),
        sleep(10000).then(() => null),
    ]);
    assert(!!toast, "Sesi diperpanjang (toast konfirmasi)");

    // Idle lagi tanpa respons → auto-logout ke /login
    let statusLogout = null;
    page.on("response", (res) => {
        if (res.url().includes("/logout")) statusLogout = res.status();
    });
    await page.waitForURL("**/login**", { timeout: 180000 }).catch(() => {});
    assert(
        page.url().includes("/login"),
        `Auto-logout ke halaman login (URL: ${page.url()}, status POST logout: ${statusLogout})`,
    );

    // Setelah logout, /admin terkunci
    await page.goto(`${BASE}/admin`, { waitUntil: "load", timeout: 30000 });
    assert(
        page.url().includes("/login"),
        "Dashboard terkunci setelah sesi habis",
    );

    assert(pageErrors.length === 0, `Bebas page error (${pageErrors.length})`);

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} asersi`);
        process.exitCode = 1;
    } else console.log("ALUR SESI MYBCA BERSIH");
} catch (e) {
    console.log("ERROR:", e.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    if (server) {
        try {
            execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: "ignore" });
        } catch {
            try {
                server.kill();
            } catch {}
        }
    }
}
