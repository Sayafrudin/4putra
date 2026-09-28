/**
 * Export hasil Apriori (aturan kuat) dari transaksi.xlsx ke JSON
 * yang dibaca halaman Collections (fitur rekomendasi, local-only).
 * Jalankan: node public/chatbot/export-apriori.js
 */
import fs from 'fs';
import path from 'path';
import { fileURLToPath } from 'url';
import { eksekusiAprioriLengkap } from './apriori.js';

const __dirname = path.dirname(fileURLToPath(import.meta.url));
const outPath = path.join(__dirname, '..', '..', 'storage', 'app', 'apriori-rekomendasi.json');

const hasil = eksekusiAprioriLengkap();

if (!hasil || hasil.aturanKuatFinal.length === 0) {
    console.error('Gagal: tidak ada strong rules untuk diekspor.');
    process.exit(1);
}

const payload = {
    dihasilkan_pada: new Date().toISOString(),
    total_transaksi: hasil.totalDatabaseTransaksi,
    aturan_kuat: hasil.aturanKuatFinal,
};

fs.writeFileSync(outPath, JSON.stringify(payload, null, 2), 'utf8');
console.log(`OK: ${payload.aturan_kuat.length} aturan kuat tersimpan di ${outPath}`);
