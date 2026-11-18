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
        `php artisan tinker --execute="App\\Models\\FinancialTab::whereIn('nama',['${NAMA_TAB}','${NAMA_TAB} 2'])->each(function ($t) { App\\Models\\Keuangan::where('financial_tab_id',$t->id)->update(['financial_tab_id'=>null]); }); App\\Models\\FinancialTab::whereIn('nama',['${NAMA_TAB}','${NAMA_TAB} 2'])->delete(); Illuminate\\Support\\Facades\\DB::table('keuangans')->where('keterangan','like','e2e-tab-uji%')->delete(); $u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} echo 'bersih';"`,
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

    await page.goto(`${BASE}/login`, { waitUntil: "load" });
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });

    await page.goto(`${BASE}/admin/keuangan`, {
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
    await sleep(900);
    let budgetVal = await page.textContent("#cardBudgetVal");
    assert(
        budgetVal.trim() === "200.000",
        `Total Budget = target (dapat "${budgetVal.trim()}")`,
    );

    // 4. Tambah biaya tambahan 50.000 → total 250.000
    await page.fill("#biaya_nama", "e2e-biaya-material");
    await page.fill("#biaya_nominal", "50000");
    await page.click("#btnBiaya");
    const barisBiaya = await Promise.race([
        page.waitForSelector('td:has-text("e2e-biaya-material")', {
            timeout: 10000,
        }),
        sleep(10000).then(() => null),
    ]);
    assert(!!barisBiaya, "Baris biaya tambahan tampil");
    budgetVal = (await page.textContent("#cardBudgetVal")).trim();
    assert(
        budgetVal === "250.000",
        `Total Budget = target + biaya (dapat "${budgetVal}")`,
    );

    // 5. Hapus biaya via modal → kembali 200.000
    await page.click('#daftarBiaya button:has-text("Hapus")');
    await page.click("#btnHapusBiaya");
    await sleep(1500);
    budgetVal = (await page.textContent("#cardBudgetVal")).trim();
    assert(
        budgetVal === "200.000",
        `Total Budget kembali setelah biaya dihapus (dapat "${budgetVal}")`,
    );

    // 6. Catat uang keluar 30.000 di tab → Sisa Budget 170.000
    await page.click("button:has-text('+ Uang Keluar')");
    await page.fill("#tambah_jumlah", "30000");
    await page.fill("#tambah_tanggal", new Date().toISOString().slice(0, 10));
    await page.fill("#tambah_ket", "e2e-tab-uji-keluar");
    await page.click("#btnTambah");
    await sleep(2000);
    const sisaVal = (await page.textContent("#cardSisaVal")).trim();
    assert(
        sisaVal === "170.000",
        `Sisa Budget = budget - keluar (dapat "${sisaVal}")`,
    );

    // 7. Rename tab
    await page.click("button[title='Ganti nama tab']");
    await page.fill("#tab_nama_rename", `${NAMA_TAB} 2`);
    await page.click("#btnTabRename");
    const tabBaru = await Promise.race([
        page.waitForSelector(`a:has-text("${NAMA_TAB} 2")`, { timeout: 10000 }),
        sleep(10000).then(() => null),
    ]);
    assert(!!tabBaru, "Nama tab berubah tanpa reload penuh");

    // 8. Export Excel menghasilkan unduhan
    const unduh = Promise.race([
        page.waitForEvent("download", { timeout: 15000 }),
        sleep(15000).then(() => null),
    ]);
    await page.click("button:has-text('Export Excel')");
    const download = await unduh;
    assert(
        !!download,
        `Export Excel mengunduh file (${download ? download.suggestedFilename() : "tidak ada"})`,
    );

    // 9. Hapus tab → transaksi pindah Kas Umum (dicek lewat UI Kas Umum)
    await page.click("button[title='Hapus tab']");
    await page.click("#btnTabHapus");
    await page.waitForURL((u) => !u.searchParams.has("tab"), {
        timeout: 15000,
    });
    await sleep(1000);
    assert(
        await page.isVisible('td:has-text("e2e-tab-uji-keluar")'),
        "Transaksi tab pindah ke Kas Umum setelah tab dihapus",
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
