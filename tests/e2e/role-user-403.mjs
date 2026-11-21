/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Akses dashboard admin untuk role user + pintu keluar halaman 403:
 *  R1  Login role user via form asli → host lokal diarahkan ke beranda publik
 *  R2  /admin bisa dibuka role user; sidebar TANPA "Manajemen Konten" & "Kelola Akun User"
 *  R3  Paksa /admin/users → 403 → klik "Kembali ke Halaman Login" → sampai /login
 *  R4  Anti-gimmick: setelah tombol diklik, sesi benar-benar mati di server (/admin → dilempar /login)
 *
 * Jalankan: npm run test:e2e
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8021;
const BASE = `http://127.0.0.1:${PORT}`;
const ROLE_USER = {
    email: "e2e-roleuser@4putra.test",
    password: "E2eRole123!",
};

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

// Watchdog anti-stuck: wajib mati sendiri maksimal 6 menit
setTimeout(
    () => {
        console.log("WATCHDOG: melewati 6 menit, proses dihentikan paksa");
        process.exit(1);
    },
    6 * 60 * 1000,
).unref();

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/login`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                if (n <= 0)
                    return reject(
                        new Error(
                            `Server tidak siap (terakhir: ${res.statusCode})`,
                        ),
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

function bersihkanUser() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ROLE_USER.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
        { stdio: "ignore", timeout: 60000 },
    );
}

let server = null;
let browser = null;

function matikanServer() {
    if (!server) return;
    // taskkill /T membunuh pohon proses (php artisan serve + PHP_CLI_SERVER_WORKERS)
    try {
        execSync(`taskkill /pid ${server.pid} /T /F`, { stdio: "ignore" });
    } catch {
        try {
            server.kill();
        } catch {
            /* sudah mati */
        }
    }
    server = null;
}

try {
    bersihkanUser();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ROLE_USER.email}'],['name'=>'E2E Role User','password'=>bcrypt('${ROLE_USER.password}'),'role'=>'user']); echo 'user siap';"`,
        { stdio: "ignore", timeout: 60000 },
    );

    server = spawn(
        "php",
        ["artisan", "serve", `--host=127.0.0.1`, `--port=${PORT}`],
        {
            env: { ...process.env, PHP_CLI_SERVER_WORKERS: "4" },
            stdio: "ignore",
        },
    );
    const status = await waitForServer();
    assert(status === 200, `Smoke HTTP /login = ${status}`);

    browser = await chromium.launch({ channel: "msedge", headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    // ---------- R1: login role user via form asli ----------
    await gotoRetry(page, `${BASE}/login`, { waitUntil: "load" });
    await page.click("#username");
    await page.fill("#username", ROLE_USER.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ROLE_USER.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/`, { timeout: 15000 });
    assert(true, "R1 login role user → beranda publik (host lokal)");

    // ---------- R2: dashboard admin terbuka, menu sensitif tidak dirender ----------
    await gotoRetry(page, `${BASE}/admin`, { waitUntil: "load" });
    assert(
        page.url().replace(/\/$/, "") === `${BASE}/admin`,
        "R2a role user masuk /admin (bukan 403)",
    );
    const sidebar = page.locator("[data-admin-sidebar]");
    await sidebar.waitFor({ state: "attached", timeout: 10000 });
    assert(
        (await sidebar.getByText("Management Farm Baru").count()) > 0,
        "R2b menu Management Farm Baru tampil",
    );
    assert(
        (await sidebar.getByText("Keuangan").count()) > 0,
        "R2c menu Keuangan tampil",
    );
    assert(
        (await sidebar.getByText("Manajemen Konten").count()) === 0,
        "R2d menu Manajemen Konten TIDAK tampil",
    );
    assert(
        (await sidebar.getByText("Kelola Akun User").count()) === 0,
        "R2e menu Kelola Akun User TIDAK tampil",
    );

    // ---------- R3: 403 di route terlarang → tombol tunggal → /login ----------
    await gotoRetry(page, `${BASE}/admin/users`, { waitUntil: "load" });
    assert(
        (await page.getByText("Akses Ditolak").count()) > 0,
        "R3a /admin/users membalas 403 Akses Ditolak",
    );
    assert(
        (await page.getByText("Kembali ke Beranda").count()) === 0,
        "R3b tombol Beranda (sumber loop) sudah dihapus",
    );
    await page.click('form[action*="admin/logout"] button[type=submit]');
    await page.waitForURL(`${BASE}/login`, { timeout: 15000 });
    assert(true, 'R3c klik "Kembali ke Halaman Login" → sampai /login');

    // ---------- R4: sesi benar-benar mati di server ----------
    await sleep(500);
    await gotoRetry(page, `${BASE}/admin`, { waitUntil: "load" });
    assert(
        page.url().includes("/login"),
        "R4a setelah tombol 403 diklik, /admin dilempar ke /login (sesi mati server-side)",
    );

    if (failures > 0) process.exitCode = 1;
} catch (err) {
    console.error("ERROR:", err.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close().catch(() => {});
    matikanServer();
    bersihkanUser(); // kebersihan data uji (AGENTS.md)
}
