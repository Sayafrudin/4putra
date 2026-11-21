/**
 * E2E dashboard profesional + form user role-aware:
 * D1 dashboard admin: kartu + grafik canvas ter-render, waktu render < 3 detik
 * D2 role user terbatas: angka keuangan tidak muncul sama sekali
 * U1 form user: pilih role Admin → semua checkbox tercentang & terkunci;
 *    kembali ke User → menu "admin" lepas & terkunci
 * Jalankan: node tests/e2e/dashboard-flow.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8033;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-dash@4putra.test", password: "E2eDash123!" };

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
        `php artisan tinker --execute="$u=App\\Models\\User::whereIn('email',['${ADMIN.email}','e2e-dash-user@4putra.test'])->get(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->whereIn('user_id',$u->pluck('id'))->delete(); $u->each(fn($x)=>$x->delete());} echo 'bersih';"`,
        { stdio: "ignore", timeout: 60000 },
    );
}

let server = null;
let browser = null;

try {
    cleanupDb();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Dash','username'=>'e2e-dash','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin','menu_access'=>null]); echo 'user siap';"`,
        { stdio: "ignore", timeout: 60000 },
    );

    server = spawn(
        "php",
        ["artisan", "serve", "--host=127.0.0.1", `--port=${PORT}`],
        {
            env: { ...process.env, PHP_CLI_SERVER_WORKERS: "4" },
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

    await gotoRetry(page, `${BASE}/login`, { waitUntil: "load" });
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });

    // ---------- D1: dashboard admin ----------
    const awal = Date.now();
    await gotoRetry(page, `${BASE}/admin`, {
        waitUntil: "load",
        timeout: 30000,
    });
    const durasi = Date.now() - awal;
    assert(durasi < 3000, `D0 render dashboard ${durasi}ms (< 3000ms)`);

    assert(
        await page.isVisible("text=Total Burung"),
        "D1a kartu Total Burung tampil",
    );
    // Data keuangan sensitif (Saldo Kas, Arus Kas, Transaksi Terbaru) DIHAPUS
    // dari dashboard untuk semua role — hanya farm stats + komposisi burung.
    assert(
        !(await page.isVisible("text=Saldo Kas Umum")),
        "D1b kartu Saldo Kas Umum TIDAK tampil (data sensitif dihapus)",
    );
    assert(
        !(await page.isVisible("text=Arus Kas 30 Hari")),
        "D1c judul grafik kas TIDAK tampil (data sensitif dihapus)",
    );
    await sleep(1200); // chart.js lazy chunk terunduh + canvas dirender
    const canvasTerisi = await page.evaluate(() => {
        return ["grafik-fase", "grafik-spesies"].map((id) => {
            const el = document.getElementById(id);
            return el && el.width > 0 && el.height > 0;
        });
    });
    assert(
        canvasTerisi.every(Boolean),
        `D1d dua canvas grafik terisi (${JSON.stringify(canvasTerisi)})`,
    );
    assert(
        !(await page.isVisible("text=Transaksi Terbaru")),
        "D1e panel transaksi terbaru TIDAK tampil (data sensitif dihapus)",
    );
    assert(
        pageErrors.length === 0,
        `D1f bebas page error (${pageErrors.length})`,
    );

    // ---------- U1: form user role-aware ----------
    await gotoRetry(page, `${BASE}/admin/users/create`, {
        waitUntil: "load",
        timeout: 30000,
    });
    await sleep(600);
    const stateUser = await page.evaluate(() => {
        const items = [...document.querySelectorAll(".menu-akses-item")];
        return items
            .filter((el) => el.dataset.akses === "admin")
            .map((el) => ({ c: el.checked, d: el.disabled }));
    });
    assert(
        stateUser.every((s) => !s.c && s.d),
        `U1a role User: menu "admin" lepas & terkunci (${JSON.stringify(stateUser)})`,
    );

    await page.selectOption("#role", "admin");
    await sleep(300);
    const stateAdmin = await page.evaluate(() => {
        const items = [...document.querySelectorAll(".menu-akses-item")];
        return {
            semua: items.every((el) => el.checked && el.disabled),
            jumlah: items.length,
        };
    });
    assert(
        stateAdmin.semua,
        `U1b role Admin: semua ${stateAdmin.jumlah} checkbox tercentang & terkunci`,
    );

    await page.selectOption("#role", "user");
    await sleep(300);
    const stateUserLagi = await page.evaluate(() => {
        const items = [...document.querySelectorAll(".menu-akses-item")];
        return {
            adminLepas: items
                .filter((el) => el.dataset.akses === "admin")
                .every((el) => !el.checked && el.disabled),
            semuaAktif: items
                .filter((el) => el.dataset.akses !== "admin")
                .every((el) => !el.disabled),
        };
    });
    assert(
        stateUserLagi.adminLepas && stateUserLagi.semuaAktif,
        "U1c balik ke User: admin lepas terkunci, sisanya aktif",
    );

    // ---------- D2: role user terbatas tidak melihat keuangan ----------
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'e2e-dash-user@4putra.test'],['name'=>'E2E Dash User','username'=>'e2e-dash-user','password'=>bcrypt('${ADMIN.password}'),'role'=>'user','menu_access'=>['kandang']]); echo 'user terbatas siap';"`,
        { stdio: "ignore", timeout: 60000 },
    );
    const ctx2 = await browser.newContext({
        viewport: { width: 1440, height: 900 },
    });
    const page2 = await ctx2.newPage();
    await gotoRetry(page2, `${BASE}/login`, { waitUntil: "load" });
    await page2.click("#username");
    await page2.fill("#username", "e2e-dash-user");
    await page2.click("#password");
    await page2.fill("#password", ADMIN.password);
    await page2.click("button[type=submit]");
    // Non-admin diarahkan ke beranda publik setelah login (desain existing), lalu buka dashboard
    await page2.waitForURL(`${BASE}/`, { timeout: 15000 });
    await gotoRetry(page2, `${BASE}/admin`, {
        waitUntil: "load",
        timeout: 30000,
    });
    assert(
        !(await page2.isVisible("text=Saldo Kas Umum")),
        "D2a kartu Saldo Kas Umum tidak tampil untuk role terbatas",
    );
    assert(
        !(await page2.isVisible("text=Arus Kas 30 Hari")),
        "D2b grafik kas tidak tampil untuk role terbatas",
    );
    assert(
        await page2.isVisible("text=Total Burung"),
        "D2c kartu farm sesuai akses tetap tampil",
    );
    await ctx2.close();

    assert(
        pageErrors.length === 0,
        `D2d bebas page error (${pageErrors.length})`,
    );

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} asersi`);
        process.exitCode = 1;
    } else console.log("DASHBOARD & FORM USER BERSIH");
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
