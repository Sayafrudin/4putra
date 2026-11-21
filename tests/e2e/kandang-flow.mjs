/**
 * E2E browser nyata: Kandang & penempatan burung (drag & drop).
 *  K1  Papan kandang dirender (blok + pool)
 *  K2  Tambah burung via modal (validasi kustom) → muncul di pool
 *  K3  Drag pool → kandang A-1 → toast sukses + SETELAH RELOAD server
 *      menunjukkan burung benar-benar di A-1 (bukan gimmick DOM saja)
 *  K4  Drag kandang → pool → server mengosongkan kandang
 *  K5  Hapus burung via modal konfirmasi → server kehilangan baris
 *  K9  Tap/klik chip (touch asli) → modal pindah kandang → server memindahkan
 *  K10 Tanpa JS (Alpine diblokir): sidebar mobile tetap tersembunyi, body tanpa scrollbar
 *  K11 Aviary: teks kosong pool tampil/hilang akurat mengikuti isi pool (drag nyata)
 *
 * Jalankan: node tests/e2e/kandang-flow.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry, loginAdmin } from "./helpers.mjs";

const PORT = 8021;
const BASE = `http://127.0.0.1:${PORT}`;
const BURUNG = "E2E Kandang";

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(
    () => {
        console.log("WATCHDOG: 8 menit tercapai, proses dihentikan paksa");
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

function tinker(code) {
    execSync(`php artisan tinker --execute="${code}"`, {
        stdio: "ignore",
        timeout: 90000,
    });
}

function cleanupDb() {
    execSync(
        `php artisan tinker --execute="App\\Models\\Burung::where('nama','like','${BURUNG}%')->delete(); echo 'bersih';"`,
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
    tinker(
        `if(App\\Models\\Kandang::count()===0){ (new Database\\Seeders\\KandangSeeder)->run(); } echo 'kandang siap';`,
    );

    server = spawn(
        "php",
        ["artisan", "serve", "--host=127.0.0.1", `--port=${PORT}`],
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

    // ---------- Login via form asli (akun admin bersama) ----------
    await loginAdmin(page, BASE);
    assert(true, "K0 login via form → /admin");

    // ---------- K1: papan kandang dirender ----------
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page.locator('h3:has-text("Blok A")').count()) > 0,
        "K1a Blok A tersedia",
    );
    assert(
        (await page.locator('[data-kandang-label="A-1"]').count()) === 1,
        "K1b kandang A-1 dirender",
    );
    assert(
        (await page.locator("#poolList").count()) === 1,
        "K1c pool burung tersedia",
    );

    // ---------- K2: tambah burung via modal ----------
    await page.click("text=+ Tambah Burung");
    await page.fill("#tambah_nama", BURUNG);
    await page.fill("#tambah_spesies", "African Grey");
    await page.selectOption("#tambah_fase", "dewasa");
    await Promise.all([
        page.waitForNavigation({ waitUntil: "load" }),
        page.click("#btnTambah"),
    ]).catch(() => {
        /* reload mungkin sudah selesai sebelum waitForNavigation mulai */
    });
    const chip = page.locator(`#poolList .burung-chip:has-text("${BURUNG}")`);
    await chip.waitFor({ state: "visible", timeout: 15000 });
    assert(
        (await chip.count()) === 1,
        "K2 burung baru muncul di pool (server-reload)",
    );

    // ---------- K3: drag pool → kandang kosong pertama (A-1 bisa saja penuh data asli) ----------
    async function dragDenganRespons(chipText, targetSel, urlPart, payload) {
        const respPromise = page
            .waitForResponse(
                (r) =>
                    r.url().includes(urlPart) &&
                    r.request().method() === "POST",
                { timeout: 30000 },
            )
            .catch(() => null); // null = tanpa fetch (guard kapasitas client-side)
        await page.evaluate(
            ({ t, sel }) => {
                const chipEl = [
                    ...document.querySelectorAll(".burung-chip"),
                ].find((c) => c.textContent.includes(t));
                const zona = document.querySelector(sel);
                const dt = new DataTransfer();
                chipEl.dispatchEvent(
                    new DragEvent("dragstart", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                zona.dispatchEvent(
                    new DragEvent("dragover", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                zona.dispatchEvent(
                    new DragEvent("drop", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                chipEl.dispatchEvent(
                    new DragEvent("dragend", {
                        bubbles: true,
                        dataTransfer: dt,
                    }),
                );
            },
            { t: chipText, sel: targetSel },
        );
        const resp = await respPromise;
        await sleep(300);
        return resp;
    }

    // Invarian teks kosong pool: pesan tampil ⇔ pool benar-benar 0 chip
    // (hermetic — pool bisa berisi burung asli lain dari data pengembangan)
    async function poolEmptyBenar(label) {
        const state = await page.evaluate(() => ({
            chips: document.querySelectorAll("#poolList .burung-chip").length,
            pesan: document.querySelectorAll("#poolList [data-pool-empty]")
                .length,
        }));
        assert(
            (state.chips === 0) === (state.pesan === 1),
            `${label} [chips=${state.chips}, pesan=${state.pesan}]`,
        );
    }

    // Kandang kosong dicari dinamis — DB pengembangan bisa punya burung asli di kandang mana pun
    async function kandangKosongLabel() {
        return await page.evaluate(() => {
            const zona = [...document.querySelectorAll(".kandang-drop")].find(
                (z) =>
                    z.querySelector(".kandang-status")?.textContent.trim() ===
                    "Kosong",
            );
            return zona ? zona.dataset.kandangLabel : null;
        });
    }

    const labelK3 = await kandangKosongLabel();
    assert(
        !!labelK3,
        `K3-pre ada kandang kosong [aktual: ${labelK3 ?? "tidak ada"}]`,
    );
    const respK3 = await dragDenganRespons(
        BURUNG,
        `[data-kandang-label="${labelK3}"]`,
        "/letakkan",
    );
    assert(
        !!respK3 && respK3.ok(),
        `K3a fetch letakkan merespons ${respK3 ? respK3.status() : "null (guard client)"}`,
    );
    await poolEmptyBenar(
        "K3d teks kosong pool konsisten dengan isi pool (tanpa reload)",
    );

    // BUKTI SERVER: reload → burung benar di kandang tujuan, pool berkurang
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page
            .locator(
                `[data-kandang-label="${labelK3}"] .burung-chip:has-text("E2E Kandang")`,
            )
            .count()) === 1,
        `K3b setelah reload, burung ada di ${labelK3} (state server)`,
    );
    assert(
        (await page
            .locator(
                `[data-kandang-label="${labelK3}"] .kandang-status:has-text("1/2")`,
            )
            .count()) === 1,
        `K3c status kandang ${labelK3} = 1/2 [aktual: ${await page
            .locator(`[data-kandang-label="${labelK3}"] .kandang-status`)
            .first()
            .textContent()
            .catch(() => "n/a")}]`,
    );

    // ---------- K4: drag kandang → pool ----------
    const respK4 = await dragDenganRespons(
        BURUNG,
        "[data-drop-pool]",
        "/kosongkan",
    );
    assert(
        !!respK4 && respK4.ok(),
        `K4a fetch kosongkan merespons ${respK4 ? respK4.status() : "null"}`,
    );
    await poolEmptyBenar(
        "K4c teks kosong pool konsisten setelah chip kembali (tanpa reload)",
    );
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page
            .locator(`#poolList .burung-chip:has-text("${BURUNG}")`)
            .count()) === 1,
        "K4b setelah reload, burung kembali di pool (kandang dikosongkan server-side)",
    );

    // ---------- K5: hapus via modal konfirmasi ----------
    await page.click(
        `#poolList .burung-chip:has-text("${BURUNG}") .hapus-burung`,
    );
    const respK5 = page
        .waitForResponse(
            (r) =>
                r.url().includes("/burung/") &&
                r.request().method() === "DELETE",
            { timeout: 30000 },
        )
        .catch(() => null);
    await page.click("#btnHapus");
    await respK5;
    await sleep(300);
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page.locator(`.burung-chip:has-text("${BURUNG}")`).count()) ===
            0,
        "K5 burung terhapus dari server (tidak ada di papan)",
    );

    // ---------- Sisi-sisi tampilan bebas error konsol ----------
    const errors = [];
    page.on("pageerror", (e) => errors.push(e.message));
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(errors.length === 0, "K6 tanpa error konsol di papan kandang");

    // ---------- K7: kapasitas maksimal 2 burung per kandang ----------
    async function buatBurung(nama, gender) {
        await page.click("text=+ Tambah Burung");
        await page.fill("#tambah_nama", nama);
        await page.fill("#tambah_spesies", "African Grey");
        await page.selectOption("#tambah_fase", "dewasa");
        if (gender) await page.selectOption("#tambah_gender", gender);
        const respB = page
            .waitForResponse(
                (r) =>
                    r.url().includes("/admin/burung") &&
                    r.request().method() === "POST",
                { timeout: 10000 },
            )
            .catch(() => null);
        await Promise.all([
            page.waitForNavigation({ waitUntil: "load" }).catch(() => {}),
            page.click("#btnTambah"),
        ]);
        const resp = await respB;
        if (!resp || !resp.ok()) {
            console.log(
                `DEBUG buatBurung(${nama}): resp=${resp ? resp.status() : "null"}`,
                errors,
            );
            await page
                .screenshot({ path: "tests/e2e/debug-k9.png", fullPage: true })
                .catch(() => {});
        }
        await page
            .locator(`#poolList .burung-chip:has-text("${nama}")`)
            .waitFor({ state: "visible", timeout: 15000 });
    }

    async function drag(chipText, targetSelector, expectFetch = true) {
        let respPromise = null;
        if (expectFetch) {
            respPromise = page
                .waitForResponse(
                    (r) =>
                        (r.url().includes("/letakkan") ||
                            r.url().includes("/kosongkan")) &&
                        r.request().method() === "POST",
                    { timeout: 30000 },
                )
                .catch(() => null);
        }
        await page.evaluate(
            ({ t, sel }) => {
                const chipEl = [
                    ...document.querySelectorAll(".burung-chip"),
                ].find((c) => c.textContent.includes(t));
                const zona = document.querySelector(sel);
                const dt = new DataTransfer();
                chipEl.dispatchEvent(
                    new DragEvent("dragstart", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                zona.dispatchEvent(
                    new DragEvent("dragover", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                zona.dispatchEvent(
                    new DragEvent("drop", {
                        bubbles: true,
                        cancelable: true,
                        dataTransfer: dt,
                    }),
                );
                chipEl.dispatchEvent(
                    new DragEvent("dragend", {
                        bubbles: true,
                        dataTransfer: dt,
                    }),
                );
            },
            { t: chipText, sel: targetSelector },
        );
        const resp = expectFetch ? await respPromise : null;
        if (!expectFetch)
            await sleep(800); // guard client menampilkan toast sinkron
        else await sleep(300);
        return resp;
    }

    await buatBurung("E2E Kandang Dua", "jantan");
    await buatBurung("E2E Kandang Tiga", null);
    await buatBurung("E2E Kandang Empat", null);

    const labelK7 = await kandangKosongLabel();
    assert(
        !!labelK7,
        `K7-pre ada kandang kosong untuk uji kapasitas [aktual: ${labelK7 ?? "tidak ada"}]`,
    );
    const respDua = await drag(
        "E2E Kandang Dua",
        `[data-kandang-label="${labelK7}"]`,
    );
    assert(
        !!respDua && respDua.ok(),
        `K7a fetch letakkan Dua merespons ${respDua ? respDua.status() : "null"}`,
    );
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page
            .locator(
                `[data-kandang-label="${labelK7}"] .kandang-status:has-text("1/2")`,
            )
            .count()) === 1,
        `K7b status ${labelK7} = 1/2 setelah burung pertama (server)`,
    );

    const respTiga = await drag(
        "E2E Kandang Tiga",
        `[data-kandang-label="${labelK7}"]`,
    );
    assert(
        !!respTiga && respTiga.ok(),
        `K7c fetch letakkan Tiga merespons ${respTiga ? respTiga.status() : "null"}`,
    );
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page
            .locator(
                `[data-kandang-label="${labelK7}"] .kandang-status:has-text("2/2 Penuh")`,
            )
            .count()) === 1,
        `K7d status ${labelK7} = 2/2 Penuh setelah burung kedua (server)`,
    );

    await drag("E2E Kandang Empat", `[data-kandang-label="${labelK7}"]`, false);
    assert(
        (await page.getByText(/maksimal/).count()) > 0,
        "K7e burung ketiga DITOLAK dengan toast penuh (guard client)",
    );

    // BUKTI SERVER: burung ketiga tetap di pool
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });
    assert(
        (await page
            .locator('#poolList .burung-chip:has-text("E2E Kandang Empat")')
            .count()) === 1,
        "K7d setelah reload, burung ketiga tetap di pool (server menolak kapasitas)",
    );

    // ---------- K11: Aviary — teks kosong pool akurat via drag nyata ----------
    await gotoRetry(page, `${BASE}/admin/aviary`, { waitUntil: "load" });
    await buatBurung("E2E Kandang Aviary", "jantan");
    const respK11a = await drag(
        "E2E Kandang Aviary",
        '[data-kandang-label="Aviary 1"]',
    );
    assert(
        !!respK11a && respK11a.ok(),
        `K11a letakkan ke Aviary 1 merespons ${respK11a ? respK11a.status() : "null"}`,
    );
    await poolEmptyBenar(
        "K11b teks kosong pool aviary konsisten setelah letakkan",
    );
    const respK11c = await drag("E2E Kandang Aviary", "[data-drop-pool]");
    assert(
        !!respK11c && respK11c.ok(),
        `K11c keluarkan dari Aviary 1 merespons ${respK11c ? respK11c.status() : "null"}`,
    );
    await poolEmptyBenar(
        "K11d teks kosong pool aviary konsisten setelah keluarkan",
    );

    // ---------- K8: tampilan mobile (viewport HP 375x812) ----------
    const mobCtx = await browser.newContext({
        viewport: { width: 375, height: 812 },
    });
    const mobile = await mobCtx.newPage();
    await loginAdmin(mobile, BASE);
    await gotoRetry(mobile, `${BASE}/admin/kandang`, { waitUntil: "load" });

    const overflow = await mobile.evaluate(
        () => document.scrollingElement.scrollWidth - window.innerWidth,
    );
    assert(
        overflow <= 1,
        `K8a tanpa scroll horizontal di HP (overflow: ${overflow}px)`,
    );

    const chipInfo = await mobile.evaluate(() => {
        const chip = [...document.querySelectorAll(".burung-chip")].find((c) =>
            c.textContent.includes("E2E Kandang Dua"),
        );
        if (!chip) return null;
        const r = chip.getBoundingClientRect();
        return {
            lebar: Math.round(r.width),
            genderTerlihat: chip.textContent.includes("Jantan"),
            namaUtuh: chip.textContent.includes("E2E Kandang Dua"),
        };
    });
    assert(
        !!chipInfo && chipInfo.genderTerlihat,
        "K8b gender (Jantan) terlihat di chip mobile",
    );
    assert(
        !!chipInfo && chipInfo.lebar >= 280,
        `K8c chip lebar besar di HP (lebar: ${chipInfo ? chipInfo.lebar : 0}px)`,
    );
    await mobCtx.close();

    // Sesi tunggal: login di context mobile menggantikan tiket halaman utama →
    // perangkat utama di-kick. Re-login dulu sebelum lanjut (perilaku sesi tunggal).
    await loginAdmin(page, BASE);
    await gotoRetry(page, `${BASE}/admin/kandang`, { waitUntil: "load" });

    // ---------- K9: tap/klik chip → modal pindah kandang (alternatif DnD di HP) ----------
    await buatBurung("E2E Kandang Lima", null);

    const touchCtx = await browser.newContext({
        viewport: { width: 375, height: 812 },
        hasTouch: true,
    });
    const touch = await touchCtx.newPage();
    await loginAdmin(touch, BASE);
    await gotoRetry(touch, `${BASE}/admin/kandang`, { waitUntil: "load" });

    // K9a tap chip di pool → modal pindah terbuka (sentuhan asli, bukan klik mouse)
    await touch.tap(`#poolList .burung-chip:has-text("E2E Kandang Lima")`);
    await touch
        .locator("#modalPindah")
        .waitFor({ state: "visible", timeout: 5000 });
    assert(
        (await touch.locator("#pindah_nama").textContent()) ===
            "E2E Kandang Lima",
        "K9a tap chip (touch) membuka modal pindah dengan nama burung",
    );
    assert(
        (await touch.locator("#btnKeluarkan").isVisible()) === false,
        "K9a2 tombol keluarkan disembunyikan untuk burung di pool",
    );

    // K9b kandang penuh (hasil K7 = 2/2) disabled di daftar
    const labelPenuh = await touch.evaluate(
        () =>
            document.querySelector(".kandang-drop[data-penuh]")?.dataset
                .kandangLabel ?? null,
    );
    assert(
        !!labelPenuh &&
            (await touch
                .locator(
                    `#pindahCageList .pindah-ke[data-label="${labelPenuh}"]`,
                )
                .isDisabled()),
        `K9b kandang penuh (${labelPenuh ?? "tidak ada"}) disabled di daftar pilihan`,
    );

    // K9c pilih kandang kosong pertama → fetch letakkan → server memindahkan
    const tujuan = await touch.evaluate(() => {
        const btn = [
            ...document.querySelectorAll("#pindahCageList .pindah-ke"),
        ].find((b) => !b.disabled);
        return btn ? { label: btn.dataset.label } : null;
    });
    assert(
        !!tujuan,
        `K9c ada kandang kosong tersedia [aktual: ${tujuan ? tujuan.label : "tidak ada"}]`,
    );
    const respK9 = touch
        .waitForResponse(
            (r) =>
                r.url().includes("/letakkan") &&
                r.request().method() === "POST",
            { timeout: 30000 },
        )
        .catch(() => null);
    await touch.tap("#pindahCageList .pindah-ke:not([disabled])");
    const r9 = await respK9;
    assert(
        !!r9 && r9.ok(),
        `K9c2 fetch letakkan merespons ${r9 ? r9.status() : "null"}`,
    );
    await touch.waitForNavigation({ waitUntil: "load" }).catch(() => {});
    if (tujuan) {
        assert(
            (await touch
                .locator(
                    `[data-kandang-label="${tujuan.label}"] .burung-chip:has-text("E2E Kandang Lima")`,
                )
                .count()) === 1,
            `K9c3 setelah reload, burung ada di kandang ${tujuan.label} (state server)`,
        );
    }

    // K9d tap chip yang sudah di kandang → keluarkan → kembali ke pool (server)
    if (tujuan) {
        await touch.tap(
            `[data-kandang-label="${tujuan.label}"] .burung-chip:has-text("E2E Kandang Lima")`,
        );
        await touch
            .locator("#btnKeluarkan")
            .waitFor({ state: "visible", timeout: 5000 });
        const respK9d = touch
            .waitForResponse(
                (r) =>
                    r.url().includes("/kosongkan") &&
                    r.request().method() === "POST",
                { timeout: 30000 },
            )
            .catch(() => null);
        await touch.tap("#btnKeluarkan");
        const r9d = await respK9d;
        assert(
            !!r9d && r9d.ok(),
            `K9d fetch kosongkan merespons ${r9d ? r9d.status() : "null"}`,
        );
        await touch.waitForNavigation({ waitUntil: "load" }).catch(() => {});
        assert(
            (await touch
                .locator(`#poolList .burung-chip:has-text("E2E Kandang Lima")`)
                .count()) === 1,
            "K9d2 setelah reload, burung kembali di pool (server)",
        );
    }
    await touchCtx.close();

    // Sesi tunggal: login touch context menggantikan tiket lagi → re-login main page
    // sebelum K10 (cookie main page dipakai context tanpa JS).
    await loginAdmin(page, BASE);

    // ---------- K10: tanpa JS (Alpine diblokir) sidebar mobile tetap tersembunyi ----------
    const noJsCtx = await browser.newContext({
        viewport: { width: 375, height: 812 },
    });
    await noJsCtx.addCookies(await context.cookies());
    await noJsCtx.route(/\/build\/assets\/.+\.js/, (route) => route.abort());
    const noJs = await noJsCtx.newPage();
    await gotoRetry(noJs, `${BASE}/admin/kandang`, { waitUntil: "load" });
    const posisiMobile = await noJs.evaluate(() =>
        document.querySelector("aside").getBoundingClientRect(),
    );
    assert(
        posisiMobile.right <= 0,
        `K10a sidebar mobile tersembunyi sebelum Alpine siap (right: ${Math.round(posisiMobile.right)}px)`,
    );
    const overflowBody = await noJs.evaluate(
        () => getComputedStyle(document.body).overflow,
    );
    assert(
        overflowBody === "visible",
        `K10c body bisa scroll di mobile (overflow: ${overflowBody}) — syarat pull-to-refresh gestur browser jalan`,
    );
    const overflowMain = await noJs.evaluate(
        () => getComputedStyle(document.querySelector("main")).overflow,
    );
    assert(
        overflowMain !== "auto" && overflowMain !== "scroll",
        `K10c2 scroll di mobile ada di body bukan <main> (overflow main: ${overflowMain})`,
    );
    await noJsCtx.close();

    // Desktop (1280px): tanpa aturan sembunyi apa pun, sidebar harus tampil
    const noJsDeskCtx = await browser.newContext({
        viewport: { width: 1280, height: 800 },
    });
    await noJsDeskCtx.addCookies(await context.cookies());
    await noJsDeskCtx.route(/\/build\/assets\/.+\.js/, (route) =>
        route.abort(),
    );
    const noJsDesk = await noJsDeskCtx.newPage();
    await gotoRetry(noJsDesk, `${BASE}/admin/kandang`, { waitUntil: "load" });
    const posisiDesktop = await noJsDesk.evaluate(() =>
        document.querySelector("aside").getBoundingClientRect(),
    );
    assert(
        posisiDesktop.left === 0,
        `K10b sidebar desktop tetap tampil (left: ${Math.round(posisiDesktop.left)}px)`,
    );
    const overflowBodyDesk = await noJsDesk.evaluate(
        () => getComputedStyle(document.body).overflow,
    );
    assert(
        overflowBodyDesk === "hidden",
        `K10c3 body tanpa scrollbar di desktop (overflow: ${overflowBodyDesk})`,
    );
    await noJsDeskCtx.close();

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} pemeriksaan`);
        process.exitCode = 1;
    } else console.log("SEMUA E2E KANDANG LULUS");
} catch (e) {
    console.log("ERROR:", e.message);
    process.exitCode = 1;
} finally {
    if (browser) await browser.close();
    cleanupDb();
    matikanServer();
}
