/**
 * php artisan serve dengan performa lokal optimal (Windows):
 *  - PHP CLI tanpa opcache membuat framework di-recompile penuh SETIAP request
 *    (~0.5-1.5s/request). Script ini membuat salinan php.ini proyek dengan
 *    opcache.enable_cli=1 dan mengaktifkannya lewat env PHPRC (diwarisi seluruh
 *    proses, tanpa butuh hak admin untuk mengedit php.ini di Program Files).
 *  - PHP_CLI_SERVER_WORKERS diabaikan PHP di Windows (fork tidak tersedia),
 *    jadi server tetap 1 worker — dengan opcache + driver file, tiap request
 *    cukup cepat sehingga antrean tidak terasa.
 *  - Hot file Vite (public/hot) basi = sumber "loading 30-60 detik": browser
 *    meminta CSS/JS ke dev server yang sudah mati hingga timeout. Saat dev
 *    server tidak lagi listen, hot file dihapus otomatis agar Laravel memakai
 *    aset build (public/build).
 * Pemakaian: npm run serve [-- --port=8021 --host=127.0.0.1]
 */
import { spawn, execFileSync } from 'node:child_process';
import { existsSync, mkdirSync, readdirSync, readFileSync, rmSync, statSync, writeFileSync } from 'node:fs';
import net from 'node:net';
import { dirname, join, resolve } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = resolve(dirname(fileURLToPath(import.meta.url)), '..');
const iniDir = join(root, '.php');
const iniPath = join(iniDir, 'php.ini');
const hotFile = join(root, 'public', 'hot');

function devServerHidup(url) {
    let u;
    try { u = new URL(url.trim()); } catch { return false; }
    const host = u.hostname.replace(/^\[|\]$/g, '');
    return new Promise((resolveProbe) => {
        const sock = net.connect({ host, port: Number(u.port || 5173), timeout: 1000 });
        sock.once('connect', () => { sock.destroy(); resolveProbe(true); });
        sock.once('timeout', () => { sock.destroy(); resolveProbe(false); });
        sock.once('error', () => resolveProbe(false));
    });
}

// Build basi = ada file source lebih baru dari manifest build terakhir;
// CSS/JS yang di-serve tidak memuat kelas/kode terbaru → layout rusak sebagian.
function buildBasi() {
    const manifest = join(root, 'public', 'build', 'manifest.json');
    if (!existsSync(manifest)) return true;
    const buildTime = statSync(manifest).mtimeMs;
    const sumber = [join(root, 'resources'), join(root, 'vite.config.js')];
    for (const s of sumber) {
        if (!existsSync(s)) continue;
        const st = statSync(s);
        if (st.isDirectory()) {
            for (const f of readdirSync(s, { recursive: true })) {
                const p = join(s, String(f));
                if (statSync(p).isFile() && statSync(p).mtimeMs > buildTime) return true;
            }
        } else if (st.mtimeMs > buildTime) return true;
    }
    return false;
}

if (existsSync(hotFile)) {
    const target = readFileSync(hotFile, 'utf8');
    if (await devServerHidup(target)) {
        console.log(`Vite dev server hidup di ${target.trim()} — aset dev dipakai (load pertama lebih lambat). Untuk demo cepat: matikan "npm run dev" lalu jalankan ulang npm run serve.`);
        if (buildBasi()) console.log('PERINGATAN: ada source lebih baru dari build terakhir — aset dev boleh basi bila dev server baru di-restart. Jalankan "npm run build" bila tampilan tidak sesuai kode terbaru.');
    } else {
        rmSync(hotFile);
        console.log('Hot file Vite basi dihapus (dev server tidak hidup) — memakai aset build.');
    }
} else if (await devServerHidup('http://[::1]:5173')) {
    console.log('PERINGATAN: Vite dev server hidup tapi public/hot tidak ada — HMR tidak aktif, situs memakai aset build. Restart "npm run dev" bila butuh hot reload.');
}

if (!existsSync(iniPath)) {
    const src = execFileSync('php', ['-r', 'echo php_ini_loaded_file();'], { encoding: 'utf8' }).trim();
    if (!src || !existsSync(src)) {
        console.error('php.ini sistem tidak ditemukan (php -r "echo php_ini_loaded_file();" kosong).');
        process.exit(1);
    }
    mkdirSync(iniDir, { recursive: true });
    const c = readFileSync(src, 'utf8')
        .replace(';zend_extension=opcache', 'zend_extension=opcache')
        .replace(';opcache.enable=1', 'opcache.enable=1')
        .replace(';opcache.enable_cli=0', 'opcache.enable_cli=1')
        .replace(';opcache.memory_consumption=128', 'opcache.memory_consumption=128')
        .replace(';opcache.interned_strings_buffer=8', 'opcache.interned_strings_buffer=16')
        .replace(';opcache.max_accelerated_files=10000', 'opcache.max_accelerated_files=20000');
    writeFileSync(iniPath, c, 'utf8');
    console.log(`php.ini proyek dibuat dari: ${src} (opcache CLI aktif)`);
}

const child = spawn('php', ['artisan', 'serve', ...process.argv.slice(2)], {
    env: { ...process.env, PHPRC: iniPath },
    stdio: 'inherit',
});

// taskkill /T: pastikan seluruh pohon proses ikut mati saat Ctrl+C
const cleanup = () => {
    try { execFileSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' }); } catch { /* sudah mati */ }
    process.exit();
};
process.on('SIGINT', cleanup);
process.on('exit', () => {
    try { execFileSync('taskkill', ['/pid', String(child.pid), '/T', '/F'], { stdio: 'ignore' }); } catch { /* sudah mati */ }
});
