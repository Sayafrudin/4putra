/**
 * E2E widget chat admin di viewport HP (375x812):
 * 1. Panel chat tidak terpotong lebar layar (bug: w-96 + right-6 = 408px > viewport).
 * 2. FAB "Admin" tidak menutupi tombol-tombol di dasar halaman (bug: konten bisa
 *    scroll sampai tepat di belakang FAB).
 * Jalankan: node tests/e2e/chat-widget-mobile.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8027;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-chatwg@4putra.test", password: "E2eChatWg123!" };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(
    () => {
        console.log("WATCHDOG: 6 menit");
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
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E ChatWg','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
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
        viewport: { width: 375, height: 812 },
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

    // ===== Skenario 1: panel chat tidak terpotong di viewport HP =====
    await gotoRetry(page, `${BASE}/admin/keuangan`, {
        waitUntil: "load",
        timeout: 30000,
    });
    await page.waitForSelector("#chat-toggle", {
        state: "visible",
        timeout: 20000,
    });
    await page.click("#chat-toggle");
    await page.waitForSelector("#chat-panel", {
        state: "visible",
        timeout: 10000,
    });
    await sleep(500);

    const panel = await page.evaluate(() => {
        const r = document.getElementById("chat-panel").getBoundingClientRect();
        return {
            left: r.left,
            right: r.right,
            width: r.width,
            vw: window.innerWidth,
            docOverflow:
                document.scrollingElement.scrollWidth - window.innerWidth,
        };
    });
    assert(
        panel.left >= 0 && panel.right <= panel.vw,
        `Panel chat di dalam viewport (left=${panel.left.toFixed(1)}, right=${panel.right.toFixed(1)}, vw=${panel.vw})`,
    );
    assert(
        panel.width <= panel.vw - 20,
        `Panel chat menyesuaikan lebar HP (width=${panel.width.toFixed(1)} ≤ vw-20=${panel.vw - 20})`,
    );
    assert(
        panel.docOverflow <= 1,
        `Tidak ada overflow horizontal dokumen saat panel terbuka (overflow=${panel.docOverflow}px)`,
    );

    // Tutup panel untuk skenario 2
    await page.click("#chat-close-btn");
    await page.waitForSelector("#chat-panel", {
        state: "hidden",
        timeout: 5000,
    });

    // ===== Skenario 2: FAB tidak menutupi tombol dasar halaman =====
    const paddingBawah = await page.evaluate(() => {
        const el = document.querySelector("main > div");
        return parseFloat(getComputedStyle(el).paddingBottom);
    });
    assert(
        paddingBawah >= 80,
        `Padding bawah konten mobile cukup (${paddingBawah}px ≥ 80px)`,
    );

    await page.evaluate(() => {
        const main = document.querySelector("main");
        main.scrollTop = main.scrollHeight;
    });
    await sleep(600);

    const tabrakan = await page.evaluate(() => {
        const fab = document
            .getElementById("chat-toggle")
            .getBoundingClientRect();
        const buttons = [
            ...document.querySelectorAll("main button, main a"),
        ].filter((b) => {
            if (b.id === "chat-toggle" || b.closest("#chat-widget"))
                return false;
            const r = b.getBoundingClientRect();
            return (
                r.width > 0 &&
                r.height > 0 &&
                r.bottom <= window.innerHeight + 50
            );
        });
        return buttons
            .filter((b) => {
                const r = b.getBoundingClientRect();
                return (
                    r.left < fab.right &&
                    r.right > fab.left &&
                    r.top < fab.bottom &&
                    r.bottom > fab.top
                );
            })
            .map((b) => (b.textContent || "").trim().slice(0, 20));
    });
    assert(
        tabrakan.length === 0,
        `Tidak ada tombol konten tertimpa FAB setelah scroll penuh (tertimpa: ${JSON.stringify(tabrakan)})`,
    );

    assert(pageErrors.length === 0, `Bebas page error (${pageErrors.length})`);

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} asersi`);
        process.exitCode = 1;
    } else console.log("WIDGET CHAT MOBILE BERSIH");
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
