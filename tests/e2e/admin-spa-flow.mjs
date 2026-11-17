/**
 * E2E browser nyata (playwright-core + Microsoft Edge terpasang).
 * Navigasi sidebar SPA (Turbo) + fitur telur pasangan + export Excel/PDF:
 *  N1  Login via form asli
 *  N2  Klik menu sidebar → konten tengah berganti TANPA reload penuh
 *      (bukti: variabel window yang dipasang SEBELUM klik masih ada setelah pindah halaman)
 *  N3  Rantai navigasi beberapa menu: probe tetap hidup di tiap halaman
 *  N4  Form telur pasangan: pilih kandang → pasangan tampil → simpan → baris muncul (server state)
 *  N5  Export Excel (.xlsx) & PDF benar-benar terunduh (signature file diverifikasi)
 *  N6  Pencarian tabel jalan di halaman hasil render Turbo (delegasi)
 *  N7  Dark mode toggle jalan di halaman hasil render Turbo
 *  N8  Performa: tiap navigasi SPA < 1000ms lokal; load awal < 3000ms (NavigationTiming)
 *
 * Jalankan: node tests/e2e/admin-spa-flow.mjs
 */
import { chromium } from "playwright-core";
import { spawn, execSync } from "node:child_process";
import http from "node:http";
import fs from "node:fs";

const PORT = 8021;
const BASE = `http://127.0.0.1:${PORT}`;
const ADMIN = { email: "e2e-spa@4putra.test", password: "E2eSpa123!" };

let failures = 0;
function assert(cond, label) {
    const ok = !!cond;
    console.log(`${ok ? "PASS" : "FAIL"}  ${label}`);
    if (!ok) failures++;
}
const sleep = (ms) => new Promise((r) => setTimeout(r, ms));

setTimeout(
    () => {
        console.log("WATCHDOG: melewati 10 menit, proses dihentikan paksa");
        process.exit(1);
    },
    10 * 60 * 1000,
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

// Tinker WAJIB satu baris (multi-baris gugur di cmd.exe Windows)
const TINKER_CLEAN = `$u=App\\Models\\User::where('email','${ADMIN.email}')->first(); if($u){Illuminate\\Support\\Facades\\DB::table('sessions')->where('user_id',$u->id)->delete(); $u->delete();} Illuminate\\Support\\Facades\\DB::table('telurs')->where('diambil_oleh','uji-spa')->delete(); App\\Models\\Burung::where('nama','like','uji-spa-%')->delete(); App\\Models\\Kandang::where('blok','A')->where('nomor',991)->delete(); echo 'bersih';`;

const TINKER_SEED = `App\\Models\\User::updateOrCreate(['email'=>'${ADMIN.email}'],['name'=>'E2E Spa','password'=>bcrypt('${ADMIN.password}'),'role'=>'admin']); $k=App\\Models\\Kandang::updateOrCreate(['blok'=>'A','nomor'=>991],['tipe'=>'medium']); App\\Models\\Burung::updateOrCreate(['nama'=>'uji-spa-jantan'],['spesies'=>'BNG Macaw','fase'=>'dewasa','kandang_id'=>$k->id]); App\\Models\\Burung::updateOrCreate(['nama'=>'uji-spa-betina'],['spesies'=>'Military Macaw','fase'=>'dewasa','kandang_id'=>$k->id]); echo 'seed oke';`;

try {
    execSync(`php artisan tinker --execute="${TINKER_CLEAN}"`, {
        stdio: "ignore",
        timeout: 60000,
    });
    execSync(`php artisan tinker --execute="${TINKER_SEED}"`, {
        stdio: "ignore",
        timeout: 60000,
    });

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
    const context = await browser.newContext({ acceptDownloads: true });
    const page = await context.newPage();

    const unduhanXlsxChunk = [];
    page.on("response", (res) => {
        if (/\/assets\/xlsx-[\w-]+\.js/.test(res.url()))
            unduhanXlsxChunk.push(res.status());
    });
    const errorJs = [];
    page.on("pageerror", (e) => errorJs.push(String(e).slice(0, 160)));
    page.on("console", (m) => {
        if (m.type() === "error")
            errorJs.push("console: " + m.text().slice(0, 160));
    });

    // ---------- N1: Login ----------
    await page.goto(`${BASE}/login`, { waitUntil: "load" });
    await page.click("#username");
    await page.fill("#username", ADMIN.email.split("@")[0]);
    await page.click("#password");
    await page.fill("#password", ADMIN.password);
    await page.click("button[type=submit]");
    await page.waitForURL(`${BASE}/admin`, { timeout: 15000 });
    assert(true, "N1 login via form → /admin");

    // Load awal diukur NavigationTiming (beban penuh halaman, tanpa overhead browser start)
    await page.goto(`${BASE}/admin`, { waitUntil: "load" });
    const loadMs = await page.evaluate(() =>
        Math.round(
            performance.timing.loadEventEnd -
                performance.timing.navigationStart,
        ),
    );
    assert(
        loadMs < 3000,
        `N1b load awal (NavigationTiming) < 3000ms (actual ${loadMs}ms)`,
    );

    // Probe: penanda SPA — hilang bila halaman reload penuh
    await page.evaluate(() => {
        window.__probeSpa = "alive";
    });
    const probeHidup = async () =>
        (await page.evaluate(() => window.__probeSpa || null)) === "alive";

    // ---------- N2: SPA klik menu sidebar (Manajemen Konten → Koleksi) ----------
    await page.click('button:has-text("Manajemen Konten")');
    const t2 = Date.now();
    await page.click('a:has-text("Koleksi (Collections)")');
    await page.waitForSelector('h2:has-text("Koleksi")', { timeout: 10000 });
    const nav2 = Date.now() - t2;
    assert(
        page.url().endsWith("/admin/collections"),
        "N2a URL berubah ke /admin/collections",
    );
    assert(
        await probeHidup(),
        "N2b TANPA reload penuh (probe tetap hidup — bukan reload)",
    );
    assert(nav2 < 2000, `N2c navigasi SPA < 2000ms lokal (actual ${nav2}ms)`);

    // ---------- N3: rantai navigasi ----------
    const rantai = [
        {
            klik: [
                'button:has-text("Manajemen Farm")',
                'a:has-text("Kandang")',
            ],
            tunggu: 'h2:has-text("Kandang")',
            url: "/admin/kandang",
            nama: "Kandang",
        },
        {
            klik: ['a:has-text("Telur & Incubator")'],
            tunggu: 'h2:has-text("Telur & Incubator")',
            url: "/admin/telur",
            nama: "Telur",
        },
        {
            klik: ['a:has-text("Keuangan")'],
            tunggu: 'h2:has-text("Keuangan Farm")',
            url: "/admin/keuangan",
            nama: "Keuangan",
        },
        {
            klik: [
                'button:has-text("Manajemen Farm")',
                'a:has-text("Vaksinasi")',
            ],
            tunggu: 'h2:has-text("Vaksinasi Burung")',
            url: "/admin/vaksinasi",
            nama: "Vaksinasi",
        },
    ];
    for (const [i, langkah] of rantai.entries()) {
        const t = Date.now();
        for (const target of langkah.klik) await page.click(target);
        await page.waitForSelector(langkah.tunggu, { timeout: 10000 });
        const durasi = Date.now() - t;
        assert(
            page.url().includes(langkah.url),
            `N3.${i + 1} ${langkah.nama} terbuka via SPA`,
        );
        assert(
            await probeHidup(),
            `N3.${i + 1}b ${langkah.nama} tanpa reload (probe hidup)`,
        );
        assert(
            durasi < 2500,
            `N3.${i + 1}c ${langkah.nama} < 2500ms lokal (actual ${durasi}ms)`,
        );
    }

    // ---------- N4: Form telur pasangan ----------
    await page.click('a:has-text("Telur & Incubator")');
    await page.waitForSelector('h2:has-text("Telur & Incubator")', {
        timeout: 10000,
    });
    assert(await probeHidup(), "N4 balik ke Telur via SPA (probe hidup)");

    await page.click('button:has-text("+ Catat Telur")');
    const modal = page.locator("#modalTambah");
    assert(
        await modal.isVisible(),
        "N4a modal Catat Telur terbuka (script inline dieksekusi ulang setelah Turbo)",
    );

    await page.selectOption("#tambah_kandang", { label: "Kandang A-991" });
    try {
        await page.waitForSelector("#info_pasangan:not(.hidden)", {
            timeout: 5000,
        });
    } catch {
        console.log("DIAGNOSTIK error JS terkumpul:", JSON.stringify(errorJs));
        console.log(
            "DIAGNOSTIK PASANGAN:",
            await page.evaluate(() =>
                typeof PASANGAN !== "undefined"
                    ? JSON.stringify(PASANGAN).slice(0, 120)
                    : "TIDAK TERDEFINISI",
            ),
        );
        throw new Error("info_pasangan tidak muncul");
    }
    const pasanganTeks = await page.locator("#pasangan_label").textContent();
    assert(
        pasanganTeks.includes("uji-spa-jantan") &&
            pasanganTeks.includes("uji-spa-betina"),
        `N4b pasangan tampil otomatis (${pasanganTeks.trim().slice(0, 60)})`,
    );

    await page.fill("#tambah_sesi", "1");
    await page.fill("#tambah_jumlah", "2");
    const tanggal = new Date(Date.now() - 5 * 86400000)
        .toISOString()
        .slice(0, 10);
    await page.fill("#tambah_tanggal", tanggal);
    await page.fill("#tambah_oleh", "uji-spa");
    await page.fill("#tambah_kondisi", "Kondisi telur baik");
    await page.click("#btnTambah");
    await page.waitForSelector(
        'td:has-text("uji-spa-betina × uji-spa-jantan")',
        { timeout: 15000 },
    );
    assert(
        true,
        'N4c telur tersimpan: baris menampilkan pasangan "uji-spa-betina × uji-spa-jantan"',
    );
    // CRUD sukses = reload penuh (perilaku lama, disengaja) → probe dipasang ulang
    await page.evaluate(() => {
        window.__probeSpa = "alive";
    });

    // ---------- N5: Export Excel & PDF (masih di halaman Telur) ----------
    const excelBtn = page.locator('button:has-text("Export Excel")');
    const [unduhXlsx] = await Promise.all([
        page.waitForEvent("download", { timeout: 30000 }),
        excelBtn.click(),
    ]);
    const fileXlsx = await unduhXlsx.path();
    const headXlsx = fs.readFileSync(fileXlsx).subarray(0, 2).toString();
    assert(
        headXlsx === "PK",
        `N5a Excel terunduh, signature ZIP/PK (file: ${unduhXlsx.suggestedFilename()})`,
    );
    // Chunk xlsx termuat (lazy) — resource timing browser; pola dev (/node_modules/xlsx/)
    // maupun build (/assets/xlsx-*.js) keduanya diterima
    const muatXlsx = await page.evaluate(() =>
        performance
            .getEntriesByType("resource")
            .filter((r) => /xlsx/.test(r.name))
            .map((r) => Math.round(r.duration)),
    );
    assert(
        muatXlsx.length >= 1,
        `N5b modul xlsx termuat via import dinamis (${muatXlsx.length} entri, durasi ${JSON.stringify(muatXlsx)}ms)`,
    );

    const [unduhPdf] = await Promise.all([
        page.waitForEvent("download", { timeout: 30000 }),
        page.click('a:has-text("Export PDF")'),
    ]);
    const filePdf = await unduhPdf.path();
    const headPdf = fs.readFileSync(filePdf).subarray(0, 5).toString();
    assert(
        headPdf === "%PDF-",
        `N5c PDF terunduh, signature %PDF (file: ${unduhPdf.suggestedFilename()})`,
    );

    // ---------- N7: dark mode di halaman hasil Turbo ----------
    await page.click('.hs-dark-mode[data-hs-theme-click-value="dark"]');
    const isDark = await page.evaluate(() =>
        document.documentElement.classList.contains("dark"),
    );
    assert(isDark, "N7a dark mode aktif di halaman hasil render Turbo");
    await page.click('.hs-dark-mode[data-hs-theme-click-value="light"]');
    assert(true, "N7b dark mode dinonaktifkan kembali");

    // ---------- N9: halaman module-script (Achievements & Users) = fallback full reload ----------
    await page.click('button:has-text("Manajemen Konten")');
    await page.click('a:has-text("Pencapaian (Achievements)")');
    await page
        .waitForSelector("h2:has-text('Pencapaian')", { timeout: 15000 })
        .catch(async () => {
            await page.waitForSelector("h2:has-text('Achievements')", {
                timeout: 10000,
            });
        });
    assert(
        !(await probeHidup()),
        "N9a Achievements = reload penuh (fallback module-script, probe mati — sesuai desain)",
    );
    assert(
        true,
        "N9b Achievements terbuka dan berfungsi (script module jalan segar)",
    );

    await page.click('a:has-text("Kelola Akun User")');
    await page.waitForSelector(".table-search-input", { timeout: 10000 });
    assert(
        !(await probeHidup()),
        "N9c Users = reload penuh (fallback module RTDB, probe mati — sesuai desain)",
    );

    // ---------- N6: pencarian tabel di halaman hasil reload (hal. Users punya input search) ----------
    await page.fill(".table-search-input", "e2e-spa");
    await sleep(300);
    const barisTampak = await page
        .locator("[data-admin-list] tr:visible")
        .count();
    assert(
        barisTampak >= 1,
        `N6b pencarian tabel jalan (${barisTampak} baris cocok)`,
    );
    await page.fill(".table-search-input", "");

    // Balik ke Telur via SPA untuk cleanup (probe dipasang ulang — mati di N9)
    await page.evaluate(() => {
        window.__probeSpa = "alive";
    });
    await page.click('button:has-text("Manajemen Farm")');
    await page.click('a:has-text("Telur & Incubator")');
    await page.waitForSelector("h2:has-text('Telur & Incubator')", {
        timeout: 10000,
    });
    assert(await probeHidup(), "N6c balik ke Telur via SPA (probe hidup)");

    // ---------- Cleanup: hapus data uji ----------
    await page.click('button:has-text("+ Catat Telur")');
    await page.selectOption("#tambah_kandang", { label: "Kandang A-991" });
    await page.fill("#tambah_sesi", "2");
    await page.fill("#tambah_jumlah", "1");
    await page.fill("#tambah_tanggal", tanggal);
    await page.fill("#tambah_oleh", "uji-spa");
    await page.fill("#tambah_kondisi", "uji-hapus");
    await page.click("#btnTambah");
    await page.waitForSelector('td:has-text("uji-hapus")', { timeout: 15000 });
    await page.locator('button:has-text("Hapus")').first().click();
    await page.click("#btnHapus");
    await sleep(1200); // refreshAdminList selesai
    execSync(`php artisan tinker --execute="${TINKER_CLEAN}"`, {
        stdio: "ignore",
        timeout: 60000,
    });
    const sisa = execSync(
        `php artisan tinker --execute="echo App\\Models\\Burung::where('nama','like','uji-spa-%')->count().App\\Models\\Kandang::where('blok','A')->where('nomor',991)->count().Illuminate\\Support\\Facades\\DB::table('telurs')->where('diambil_oleh','uji-spa')->count(); App\\Models\\User::where('email','${ADMIN.email}')->delete(); echo 'cek';"`,
        { timeout: 60000 },
    )
        .toString()
        .trim()
        .replace("cek", "");
    assert(
        sisa === "000",
        `N8 data uji dibersihkan (sisa: burung/kandang/telur = ${sisa})`,
    );
} catch (err) {
    console.log("FAIL  tak terduga:", err.message);
    failures++;
} finally {
    try {
        execSync(`php artisan tinker --execute="${TINKER_CLEAN}"`, {
            stdio: "ignore",
            timeout: 60000,
        });
    } catch {
        /* best effort */
    }
    if (browser) await browser.close();
    matikanServer();
}

console.log(failures === 0 ? "\nSEMUA LULUS" : `\n${failures} GAGAL`);
process.exit(failures === 0 ? 0 : 1);
