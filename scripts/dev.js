/**
 * Jalankan php artisan serve (via scripts/serve.js: opcache + hot file) dan
 * Vite dev server dalam SATU perintah — pengganti "php artisan serve & npm run dev"
 * yang selalu gagal parse di PowerShell (& bukan pemisah perintah di sana).
 * Ctrl+C mematikan seluruh pohon proses keduanya (taskkill /T), tanpa proses yatim.
 * Pemakaian: npm run dev:all [-- --port=8021 --host=127.0.0.1]
 */
import { spawn, execFileSync } from "node:child_process";
import { resolve } from "node:path";
import { dirname } from "node:path";
import { fileURLToPath } from "node:url";

const root = resolve(dirname(fileURLToPath(import.meta.url)), "..");
const anak = [];

function matikanSemua() {
    for (const p of anak) {
        try {
            execFileSync("taskkill", ["/pid", String(p.pid), "/T", "/F"], {
                stdio: "ignore",
            });
        } catch {
            /* sudah mati */
        }
    }
}

process.on("SIGINT", () => {
    matikanSemua();
    process.exit(0);
});
process.on("exit", matikanSemua);

const serveArgs = ["scripts/serve.js", ...process.argv.slice(2)];
const viteArgs = ["node_modules/vite/bin/vite.js"];

// Vite di depan: hot file public/hot dibuat segera, artisan serve yang start belakangan
// langsung melihatnya dan memakai aset dev (HMR aktif sejak request pertama).
anak.push(spawn("node", viteArgs, { cwd: root, stdio: "inherit" }));
anak.push(spawn("node", serveArgs, { cwd: root, stdio: "inherit" }));

for (const p of anak) {
    p.on("exit", (code) => {
        if (code !== 0 && code !== null) {
            console.error(
                `\nProses keluar dengan kode ${code} — mematikan semua...`,
            );
            matikanSemua();
            process.exit(code);
        }
    });
}
