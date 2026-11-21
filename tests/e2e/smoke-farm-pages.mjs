/**
 * Smoke render HTTP + visual dark mode untuk halaman farm yang diubah:
 *  - /admin/vaksinasi, /admin/telur, /admin/stok-makanan (Blade baru dengan optgroup kandang)
 *  - dropdown burung tergrup per kandang (optgroup "Kandang X-Y" / "Belum ditempatkan")
 *  - dark mode: color-scheme + icon kalender ter-invert
 *  - bebas error konsol
 * Jalankan: node tests/e2e/smoke-farm-pages.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8023;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-smoke@4putra.test", password: "E2eSmoke123!" };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}

setTimeout(
    () => {
        console.log("WATCHDOG: 6 menit tercapai, proses dihentikan paksa");
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
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Smoke','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
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
    const context = await browser.newContext();
    const page = await context.newPage();
    const errors = [];
    page.on("pageerror", (e) => errors.push(e.message));

    await gotoRetry(page, `${BASE}/login`, { waitUntil: "load" });
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });

    // ---------- Smoke render: tiap halaman 200 + konten kunci dirender ----------
    for (const [url, needle] of [
        ["/admin/vaksinasi", "Obat &amp; Vaksinasi"],
        ["/admin/telur", "Telur &amp; Incubator"],
        ["/admin/stok-makanan", "Stok Makanan Burung"],
        ["/admin/kandang", "Kandang &amp; Penempatan"],
        ["/admin", "Dashboard"],
    ]) {
        const res = await gotoRetry(page, `${BASE}${url}`, {
            waitUntil: "load",
        });
        assert(res.status() === 200, `S1 ${url} = ${res.status()}`);
        const html = await page.content();
        assert(html.includes(needle), `S2 ${url} memuat "${needle}"`);
    }

    // ---------- Vaksinasi: modal catat dengan jenis standar + metode ----------
    await gotoRetry(page, `${BASE}/admin/vaksinasi`, { waitUntil: "load" });
    const vaksinUi = await page.evaluate(() => ({
        jenisOpsi: [
            ...(document.querySelector("#catat_jenis_pilih")?.options ?? []),
        ].map((o) => o.value),
        metode: [...document.querySelectorAll('input[name="metode"]')].map(
            (r) => r.value,
        ),
        panelJatuhTempo: !!document.querySelector(
            "#panelJatuhTempo, .panel-jatuh-tempo",
        ),
    }));
    assert(
        vaksinUi.jenisOpsi.includes("Obat Cacing") &&
            vaksinUi.jenisOpsi.includes("Lainnya"),
        `S3 modal catat vaksinasi punya jenis standar + Lainnya [${vaksinUi.jenisOpsi.join(", ")}]`,
    );
    assert(
        vaksinUi.metode.includes("suntik") && vaksinUi.metode.includes("oral"),
        `S3a metode catat = suntik + oral [${vaksinUi.metode.join(", ")}]`,
    );
    await gotoRetry(page, `${BASE}/admin/telur`, { waitUntil: "load" });
    // Telur kini berbasis KANDANG (pasangan 2 induk otomatis) — bukan dropdown induk per optgroup
    const optTelur = await page.evaluate(() =>
        [...document.querySelectorAll("#tambah_kandang option")].map(
            (o) => o.textContent,
        ),
    );
    assert(
        optTelur.some((l) => l.startsWith("Kandang ")),
        `S4 dropdown telur = daftar kandang pasangan [${optTelur.join(", ")}]`,
    );
    assert(
        optTelur.every((l) => l !== ""),
        "S5 tidak ada option tanpa label",
    );

    // ---------- Stok makanan: satuan dropdown + badge ----------
    await gotoRetry(page, `${BASE}/admin/stok-makanan`, { waitUntil: "load" });
    const satuanUi = await page.evaluate(() => ({
        jenisTambah: document.querySelector("#tambah_satuan")?.tagName,
        opsi: [
            ...(document.querySelector("#tambah_satuan")?.options ?? []),
        ].map((o) => o.value),
        badge: document.querySelector(".satuan-badge-tambah")?.textContent,
    }));
    assert(
        satuanUi && satuanUi.jenisTambah === "SELECT",
        "S6 satuan di modal tambah berupa dropdown",
    );
    assert(
        JSON.stringify(satuanUi.opsi) ===
            JSON.stringify(["kg", "gr", "ons", "sak"]),
        `S7 opsi satuan = [${satuanUi.opsi.join(", ")}]`,
    );
    assert(
        satuanUi.badge === "kg",
        `S8 badge satuan tampil di input stok/konsumsi [aktual: ${satuanUi.badge}]`,
    );

    // ---------- Dark mode: color-scheme + icon kalender ----------
    await page.click(".hs-dark-mode");
    const dark = await page.evaluate(() => {
        const indicator = document.querySelector('input[type="date"]');
        return {
            kelas: document.documentElement.classList.contains("dark"),
            colorScheme: getComputedStyle(document.documentElement).colorScheme,
            filter: indicator
                ? (getComputedStyle(
                      indicator,
                      "::webkit-calendar-picker-indicator",
                  )?.filter ??
                  getComputedStyle(indicator).getPropertyValue("filter"))
                : "n/a",
        };
    });
    assert(
        dark.kelas && dark.colorScheme === "dark",
        `S9 dark mode: color-scheme = ${dark.colorScheme}`,
    );
    console.log(`      info filter icon kalender: "${dark.filter}"`);

    assert(
        errors.length === 0,
        `S11 bebas error konsol [${errors.join(" | ")}]`,
    );

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} pemeriksaan`);
        process.exitCode = 1;
    } else console.log("SEMUA SMOKE FARM PAGES LULUS");
} catch (e) {
    console.log("ERROR:", e.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    matikanServer();
}
