/**
 * E2E sweep mobile: seluruh halaman admin pada viewport HP (375x812).
 * Kriteria objektif: tidak ada overflow horizontal pada level dokumen.
 * Jalankan: node tests/e2e/admin-mobile.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry, loginAdmin } from "./helpers.mjs";

const PORT = 8023;
const BASE = `http://127.0.0.1:${PORT}`;

const PAGES = [
    ["/admin", "Dashboard"],
    ["/admin/collections", "Collections"],
    ["/admin/achievements", "Achievements"],
    ["/admin/daily-activities", "Daily Activities"],
    ["/admin/facilities", "Facilities"],
    ["/admin/users", "Users"],
    ["/admin/users/create", "Users create"],
    ["/admin/profile", "Profile"],
    ["/admin/chatbot", "Chatbot ringkasan"],
    ["/admin/chatbot/inventaris", "Chatbot inventaris"],
    ["/admin/chatbot/transaksi", "Chatbot transaksi"],
    ["/admin/chatbot/chat", "Chatbot WA chat"],
    ["/admin/kandang", "Kandang"],
    ["/admin/vaksinasi", "Vaksinasi"],
    ["/admin/telur", "Telur & Incubator"],
    ["/admin/keuangan", "Keuangan"],
    ["/admin/stok-makanan", "Stok Makanan"],
];

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

let server = null;
let browser = null;

try {
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

    await loginAdmin(page, BASE);

    for (const [path, label] of PAGES) {
        const errors = [];
        const onErr = (e) => errors.push(e.message);
        page.on("pageerror", onErr);
        try {
            await gotoRetry(page, `${BASE}${path}`);
            await sleep(800);
            const overflow = await page.evaluate(
                () => document.scrollingElement.scrollWidth - window.innerWidth,
            );
            assert(
                overflow <= 1 && errors.length === 0,
                `${label} (${path}) — overflow ${overflow}px, console errors: ${errors.length}`,
            );
        } catch (e) {
            assert(
                false,
                `${label} (${path}) — ERROR: ${e.message.slice(0, 120)}`,
            );
        } finally {
            page.off("pageerror", onErr);
        }
    }

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} halaman bermasalah`);
        process.exitCode = 1;
    } else console.log("SEMUA HALAMAN ADMIN BERSIH DI MOBILE");
} catch (e) {
    console.log("ERROR:", e.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
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
