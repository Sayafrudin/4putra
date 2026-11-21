/**
 * E2E alur Multi-Tab Keuangan Proyek:
 * buat tab → set target anggaran → tambah biaya → total budget naik →
 * catat pengeluaran → sisa budget turun → rename tab → hapus tab
 * (transaksi pindah Kas Umum) → data uji dibersihkan.
 * Jalankan: node tests/e2e/keuangan-tab-flow.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import { gotoRetry } from "./helpers.mjs";

const PORT = 8029;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-keutab@4putra.test", password: "E2eKeuTab123!" };
const NAMA_TAB = "E2E Proyek Uji";

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
        // Sapu pasangan transfer berdasarkan transfer_id (kedua sisi sekaligus),
        // baris uji berketerangan e2e-tab-uji%, lalu tab uji & akun E2E.
        `php artisan tinker --execute="App\\Models\\Keuangan::where(function ($q) { $q->whereIn('transfer_id', function ($s) { $s->from('keuangans')->select('transfer_id')->where('keterangan','like','%E2E Proyek Uji%')->whereNotNull('transfer_id'); })->orWhere('keterangan','like','%E2E Proyek Uji%'); })->orWhere('keterangan','like','e2e-tab-uji%')->delete(); App\\Models\\FinancialTab::where('nama','${NAMA_TAB}')->orWhere('nama','${NAMA_TAB} 2')->delete(); $u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
        { stdio: "ignore", timeout: 90000 },
    );
}

let server = null;
let browser = null;

try {
    cleanupDb();
    execSync(
        `php artisan tinker --execute="App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E KeuTab','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); echo 'user siap';"`,
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

    await gotoRetry(page, `${BASE}/admin/keuangan`, {
        waitUntil: "load",
        timeout: 30000,
    });
    await sleep(800);

    // 1. Kas Umum aktif: kartu Saldo (3 kartu desktop), tanpa panel budget
    assert(await page.isVisible("text=Kas Umum"), "Tab Kas Umum tampil");
    assert(
        !(await page.isVisible("#budget_target")),
        "Panel budget tidak tampil di Kas Umum",
    );

    // 2. Buat tab proyek via (+)
    await page.click("button[title='Tab proyek baru']");
    await page.fill("#tab_nama_baru", NAMA_TAB);
    await page.click("#btnTabBaru");
    await page.waitForURL("**/admin/keuangan?tab=*", { timeout: 15000 });
    await sleep(900);
    assert(
        await page.isVisible("#budget_target"),
        "Panel budget tampil di tab proyek",
    );
    assert(
        await page.isVisible("#cardBudgetVal"),
        "Kartu Total Budget tampil di tab proyek",
    );
    const tabUrl = page.url();

    // 3. Set target anggaran 200.000
    await page.fill("#budget_target", "200000");
    await page.click("#btnBudget");
    let tercapai = true;
    try {
        await page.waitForFunction(
            () =>
                document.getElementById("cardBudgetVal").textContent.trim() ===
                "200.000",
            null,
            { timeout: 15000 },
        );
    } catch {
        tercapai = false;
    }
    assert(tercapai, "Total Budget = target 200.000");

    // 4. Tambah biaya tambahan 50.000 → total 250.000
    await page.fill("#biaya_nama", "e2e-biaya-material");
    await page.fill("#biaya_nominal", "50000");
    await page.click("#btnBiaya");
    const barisBiaya = await Promise.race([
        page.waitForSelector('td:has-text("e2e-biaya-material")', {
            timeout: 12000,
        }),
        sleep(12000).then(() => null),
    ]);
    assert(!!barisBiaya, "Baris biaya tambahan tampil");
    await page.waitForFunction(
        () =>
            document.getElementById("cardBudgetVal").textContent.trim() ===
            "250.000",
        null,
        { timeout: 15000 },
    );
    assert(true, "Total Budget = target + biaya (250.000)");

    // 5. Hapus biaya via modal → kembali 200.000
    await page.click('#daftarBiaya button:has-text("Hapus")');
    await page.click("#btnHapusBiaya");
    await page.waitForFunction(
        () =>
            document.getElementById("cardBudgetVal").textContent.trim() ===
            "200.000",
        null,
        { timeout: 15000 },
    );
    assert(true, "Total Budget kembali setelah biaya dihapus");

    // 6. Catat uang keluar 30.000 di tab → Sisa Budget 170.000
    await page.click("button:has-text('+ Uang Keluar')");
    await page.fill("#tambah_jumlah", "30000");
    await page.fill("#tambah_tanggal", new Date().toISOString().slice(0, 10));
    await page.fill("#tambah_ket", "e2e-tab-uji-keluar");
    await page.click("#btnTambah");
    await page.waitForFunction(
        () =>
            document.getElementById("cardSisaVal").textContent.trim() ===
            "170.000",
        null,
        { timeout: 15000 },
    );
    assert(true, "Sisa Budget = budget - keluar (170.000)");

    // 6b. Edit transaksi (typo fix): 30.000 → 35.000 → Sisa Budget 165.000
    await page.click(
        "tr:has-text('e2e-tab-uji-keluar') button:has-text('Edit')",
    );
    await page.waitForSelector("#modalEdit:not(.hidden)", { timeout: 5000 });
    await page.fill("#edit_jumlah", "35000");
    await page.click("#modalEdit button[type=submit]");
    await page.waitForFunction(
        () =>
            document.getElementById("cardSisaVal").textContent.trim() ===
            "165.000",
        null,
        { timeout: 15000 },
    );
    assert(true, "Edit transaksi tersimpan — Sisa Budget = 165.000");

    // 7. Transfer dana: Kas Umum → tab, 50.000
    await page.click("button:has-text('+ Transfer Dana')");
    await page.selectOption("#transfer_dari", "kas_umum");
    await page.selectOption("#transfer_ke", { label: NAMA_TAB });
    await page.fill("#transfer_nominal", "50000");
    await page.fill("#transfer_ket", "e2e-transfer-uji");
    await page.click("#btnTransfer");
    const barisTransfer = await Promise.race([
        page.waitForSelector('td:has-text("Dari Kas Umum")', {
            timeout: 12000,
        }),
        sleep(12000).then(() => null),
    ]);
    assert(!!barisTransfer, 'Transfer tercatat di tab (baris "Dari Kas Umum")');

    // 8. Hapus tab DIBLOK karena masih ada transaksi:
    // tombol "Hapus Tab (kosong)" disabled + tombol massal tersedia.
    await page.click("button[title='Hapus tab']");
    await page.waitForSelector("#btnTabHapusSemua", { timeout: 5000 });
    const btnKosongDisabled = await page.$eval(
        "#btnTabHapus",
        (el) => el.disabled,
    );
    assert(
        btnKosongDisabled,
        "Hapus tab diblok — tombol kosong disabled saat tab berisi transaksi",
    );
    assert(
        await page.isVisible("#btnTabHapusSemua"),
        "Tombol Hapus Semua (N transaksi) + Tab tersedia di modal",
    );
    await page.click("#modalTabHapus button:has-text('Batal')");
    await sleep(400);

    // 9. Kosongkan tab: hapus baris transfer (kedua sisi ikut) + uang keluar
    await page.click("tr:has-text('Dari Kas Umum') button:has-text('Hapus')");
    await page.click("#btnHapus");
    await sleep(2000);
    await page.click(
        "tr:has-text('e2e-tab-uji-keluar') button:has-text('Hapus')",
    );
    await page.click("#btnHapus");
    await sleep(2000);

    // 10. Tab kosong → hapus tab berhasil
    await page.click("button[title='Hapus tab']");
    await page.click("#btnTabHapus");
    await page.waitForURL((u) => !u.searchParams.has("tab"), {
        timeout: 15000,
    });
    await sleep(1000);
    assert(
        !(await page.isVisible(`a:has-text("${NAMA_TAB}")`)),
        "Tab terhapus setelah kosong",
    );
    assert(
        !(await page.isVisible('td:has-text("e2e-transfer-uji")')),
        "Tidak ada residu transfer di Kas Umum (pasangan ikut terhapus)",
    );

    // 11. Hapus massal: tab baru berisi transaksi → satu klik bersihkan semua
    await page.click("button[title='Tab proyek baru']");
    await page.fill("#tab_nama_baru", `${NAMA_TAB} 2`);
    await page.click("#btnTabBaru");
    await page.waitForURL("**/admin/keuangan?tab=*", { timeout: 15000 });
    await sleep(900);
    await page.click("button:has-text('+ Uang Keluar')");
    await page.fill("#tambah_jumlah", "12000");
    await page.fill("#tambah_tanggal", new Date().toISOString().slice(0, 10));
    await page.fill("#tambah_ket", "e2e-tab-uji-massal");
    await page.click("#btnTambah");
    await page.waitForSelector('td:has-text("e2e-tab-uji-massal")', {
        timeout: 12000,
    });
    await page.click("button[title='Hapus tab']");
    await page.waitForSelector("#btnTabHapusSemua", { timeout: 5000 });
    await page.click("#btnTabHapusSemua");
    await page.waitForURL((u) => !u.searchParams.has("tab"), {
        timeout: 15000,
    });
    await sleep(1000);
    assert(
        !(await page.isVisible('td:has-text("e2e-tab-uji-massal")')),
        "Hapus massal: transaksi tab ikut terhapus",
    );
    assert(
        !(await page.isVisible(`a:has-text("${NAMA_TAB} 2")`)),
        "Hapus massal: tab terhapus sekali klik",
    );

    assert(pageErrors.length === 0, `Bebas page error (${pageErrors.length})`);

    console.log("==================================================");
    if (failures > 0) {
        console.log(`GAGAL: ${failures} asersi`);
        process.exitCode = 1;
    } else console.log("ALUR MULTI-TAB KEUANGAN BERSIH");
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
