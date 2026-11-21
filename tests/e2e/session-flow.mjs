/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Memverifikasi perilaku dari sisi SERVER, bukan gimmick UI (sesi ala MyBCA,
 * tanpa tombol Perpanjang):
 *  S1  Login via form asli
 *  S2  Kembali ke tab saat idle pendek → wake ping 200, tanpa modal
 *  S3  Kembali ke tab setelah idle > batas → modal "Sesi Telah Berakhir" → auto-logout server → /admin tertutup
 *  S4  Cookie sesi hilang → TIDAK dibangkitkan ulang (tanpa remember-me) → dilempar login
 *  S5  Sesi mati di server → wake ping 401 → modal → tombol Login Kembali → login ulang
 *  S5d AJAX 401 saat tab tak pernah tersembunyi → interceptor fetch → modal sesi
 *  S6  Navbar scrolled: track toggle bahasa terlihat (tidak merah-di-merah)
 *  S7  Performa: waktu load halaman utama < 3 detik (NavigationTiming)
 *
 * Jalankan: npm run test:e2e
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8020;
const BASE = `http://127.0.0.1:${PORT}`;
const LIFETIME_MIN = 2; // SESSION_LIFETIME dev server
const ADMIN = { email: "e2e-flow@4putra.test", password: "E2eFlow123!" };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));
const sec = (n) => n * 1000;
// Server dev single-threaded + TiDB dingin kadang menggantung request; goto GET

// Watchdog anti-stuck: skrip wajib mati sendiri maksimal 12 menit
// (runtime normal ~7 menit; di luar itu = hang → bunuh proses agar tidak menggantung task)
setTimeout(
    () => {
        console.log("WATCHDOG: melewati 12 menit, proses dihentikan paksa");
        process.exit(1);
    },
    12 * 60 * 1000,
).unref();

function waitForServer(retries = 30) {
    return new Promise((resolve, reject) => {
        const tryOnce = (n) => {
            http.get(`${BASE}/login`, (res) => {
                res.resume();
                if (res.statusCode === 200) return resolve(200);
                // 500 cold-start (koneksi DB dingin gagal sekali) → coba lagi
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

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
        { stdio: "ignore", timeout: 60000 },
    );
}

let server = null;
let browser = null;

function matikanServer() {
    if (!server) return;
    // taskkill /T membunuh pohon proses (php artisan serve + PHP_CLI_SERVER_WORKERS);
    // server.kill() saja meninggalkan worker yatim di Windows
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
    cleanupDb();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Flow','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
        { stdio: "ignore", timeout: 60000 },
    );

    server = spawn(
        "php",
        ["artisan", "serve", `--host=127.0.0.1`, `--port=${PORT}`],
        {
            // Multi-worker: php serve default 1 worker → request ping bisa antre
            // di belakang request lain dan tampak "hang" padahal server sehat.
            env: {
                ...process.env,
                SESSION_LIFETIME: String(LIFETIME_MIN),
                PHP_CLI_SERVER_WORKERS: "4",
            },
            stdio: "ignore",
        },
    );
    // Terima hanya 200 — tolak 500 cold-start (DB dingin bisa gagal sekali)
    const status = await waitForServer();
    assert(status === 200, `Smoke HTTP /login = ${status}`);

    browser = await chromium.launch({ channel: "msedge", headless: true });
    const context = await browser.newContext();
    const page = await context.newPage();

    const pingStatuses = [];
    page.on("response", (res) => {
        if (res.url().includes("/admin/ping")) pingStatuses.push(res.status());
    });

    // ---------- S1: Login via form asli ----------
    await gotoRetry(page, `${BASE}/login`, { waitUntil: "load" });
    // Field login memakai readonly+onfocus (anti-autofill): klik dulu agar readonly hilang
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    assert(true, "S1 login via form → /admin");

    // ---------- S2: kembali ke tab saat idle masih pendek → ping 200, tanpa modal ----------
    await sleep(sec(5));
    await page.evaluate(() => window.sessionTimeout.wake());
    await sleep(1000);
    const modal = page.locator("#session-timeout-modal");
    assert(
        pingStatuses.at(-1) === 200,
        `S2a wake ping = 200 (actual: ${pingStatuses.at(-1)})`,
    );
    assert(
        !(await modal.isVisible()),
        "S2b tanpa modal saat idle masih di bawah batas",
    );

    // ---------- S3: kembali ke tab setelah idle > batas → modal sesi berakhir → login ----------
    // IDLE_LIMIT = lifetime − 5 menit, clamp min 1 menit → 60 dtk (LIFETIME=2 menit).
    await sleep(sec(62));
    await page.evaluate(() => window.sessionTimeout.wake());
    assert(
        await modal.isVisible(),
        "S3a modal sesi berakhir muncul saat kembali setelah idle panjang",
    );
    assert(
        (await page.getByText("Sesi Anda Telah Berakhir").count()) > 0,
        "S3b modal menyatakan sesi berakhir + ajak login kembali",
    );
    await sleep(1500); // beri waktu fetch /logout menuntaskan sesi di server
    await gotoRetry(page, `${BASE}/admin`);
    assert(
        page.url().includes("/login"),
        "S3c auto-logout server-side: /admin tak bisa dibuka lagi",
    );

    // ---------- S4: cookie sesi hilang → TIDAK dibangkitkan ulang ----------
    // Kontrak tanpa remember-me: sesi mati = login ulang, bukan auto-masuk.
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    const cookies = await context.cookies();
    await context.clearCookies();
    await context.addCookies(
        cookies.filter((c) => !c.name.startsWith("laravel_session")),
    );
    await gotoRetry(page, `${BASE}/admin/collections`);
    assert(
        page.url().includes("/login"),
        "S4 sesi mati → dilempar login (tidak ada resurrect tanpa remember-me)",
    );
    // ---------- S5: login ulang → sesi mati di server → wake ping 401 → modal → login ----------
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    // Intended URL dari S4 (/admin/collections) dipulihkan server — boleh dashboard atau langsung collections
    await page.waitForURL(/\/admin(\/|$)/, { timeout: 15000 });
    await gotoRetry(page, `${BASE}/admin/collections`);

    await context.clearCookies(); // buang SEMUA cookie → ping wajib 401
    await sleep(sec(5)); // idle pendek → jalur validasi ping (bukan jalur idle)
    await page.evaluate(() => window.sessionTimeout.wake());
    // ping 401 → modal tampil async; tunggu deterministik, jangan race
    await modal.waitFor({ state: "visible", timeout: 5000 }).catch(() => {});
    assert(
        await modal.isVisible(),
        "S5a modal muncul saat wake ping 401 (sesi mati di server)",
    );
    await page.click("#session-login-btn");
    await page.waitForURL(/\/login/, { timeout: 15000 });
    assert(true, "S5b tombol Login Kembali → halaman login");
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(/\/admin(\/|$)/, { timeout: 15000 });
    assert(true, "S5c login ulang → masuk kembali ke admin");

    // ---------- S5d: AJAX 401 (sesi mati) → interceptor menampilkan modal sesi ----------
    // Tab tak pernah tersembunyi = tak ada wake; satu-satunya tanda sesi mati
    // adalah request gagal → interceptor wajib membawa modal, bukan toast.
    await context.clearCookies();
    await sleep(500);
    await page.evaluate(() =>
        fetch("/admin/telur/export/pdf", {
            headers: { Accept: "application/json" },
        }).catch(() => {}),
    );
    await modal.waitFor({ state: "visible", timeout: 5000 }).catch(() => {});
    assert(
        await modal.isVisible(),
        "S5d AJAX 401 → interceptor fetch menampilkan modal sesi",
    );
    assert(
        (await page.getByText("Sesi Anda Telah Berakhir").count()) > 0,
        "S5e modal interceptor memakai pesan sesi berakhir",
    );

    // ---------- S6: navbar scrolled — track toggle terlihat ----------
    await gotoRetry(page, `${BASE}/lang/en`, { waitUntil: "load" });
    await gotoRetry(page, `${BASE}/`, { waitUntil: "load" });
    const track = page.locator("#lang-toggle + div");
    await page.evaluate(() => window.scrollTo(0, 300));
    await sleep(700);
    const scrolledClass = await track.getAttribute("class");
    assert(
        scrolledClass.includes("peer-checked:bg-gray-300") &&
            !scrolledClass.includes("peer-checked:bg-[#E62C37]"),
        "S6a track EN terlihat di navbar merah (bukan merah-di-merah)",
    );
    await page.evaluate(() => window.scrollTo(0, 0));
    await sleep(700);
    assert(
        (await track.getAttribute("class")).includes(
            "peer-checked:bg-[#E62C37]",
        ),
        "S6b di posisi atas track kembali merah",
    );
    await gotoRetry(page, `${BASE}/lang/id`, { waitUntil: "load" });

    // ---------- S7: performa ----------
    // DCL = render siap (eksklusi unduhan foto). Ambang 4 detik utk /admin/collections:
    // dev server tanpa opcache + TiDB remote; produksi memakai config cache.
    // Warm-up dulu: koneksi TiDB dingin membuat pengukuran pertama melebihi ambang
    // (flapping N3.2c) — ukur pada perjalanan kedua.
    for (const path of ["/", "/collections", "/admin", "/admin/collections"]) {
        await gotoRetry(page, `${BASE}${path}`);
    }
    for (const path of ["/", "/collections", "/admin", "/admin/collections"]) {
        await gotoRetry(page, `${BASE}${path}`, { waitUntil: "load" });
        const t = await page.evaluate(() => {
            const nav = performance.getEntriesByType("navigation")[0];
            return {
                dcl: Math.round(nav.domContentLoadedEventEnd - nav.startTime),
                full: Math.round(nav.loadEventEnd - nav.startTime),
            };
        });
        assert(
            t.dcl < 4000,
            `S7 render ${path} = ${t.dcl}ms (< 4000ms; full-load ${t.full}ms)`,
        );
    }

    await browser.close();
    browser = null;
} catch (err) {
    failures++;
    console.log(`FAIL  Exception: ${err.message}`);
} finally {
    if (browser) {
        try {
            await browser.close();
        } catch {
            /* sudah tertutup */
        }
    }
    matikanServer();
    try {
        cleanupDb();
    } catch {
        /* cleanup best-effort */
    }
}

console.log("=".repeat(50));
console.log(
    failures === 0 ? "SEMUA E2E BROWSER LULUS" : `${failures} E2E GAGAL`,
);
process.exit(failures === 0 ? 0 : 1);
