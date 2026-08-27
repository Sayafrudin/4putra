import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';
import fs from 'fs';

const __dirname = dirname(fileURLToPath(import.meta.url));
config({ path: join(__dirname, '../../.env') });
config({ path: join(__dirname, '.env'), override: true });
import { makeWASocket, useMultiFileAuthState, DisconnectReason } from '@whiskeysockets/baileys';
import pino from 'pino';
import { Groq } from 'groq-sdk';
import express from 'express';
import { eksekusiAprioriLengkap } from './apriori.js';
import { query, queryOne, insert, update } from './db.js';
import { createQrisCharge, unduhGambarQr } from './midtrans.js';
import { kirimHandoffKeFirebase, hapusHandoffFirebase } from './firebase.js';

// ============================================================
// KONFIGURASI
// ============================================================
const GROQ_API_KEY = process.env.GROQ_API_KEY;
// Model default mengikuti katalog Groq aktif; ganti via GROQ_MODEL di .env
const GROQ_MODEL = process.env.GROQ_MODEL || 'openai/gpt-oss-20b';
const groq = new Groq({ apiKey: GROQ_API_KEY });
const RATE_LIMIT_MS = 2000;
const JUMLAH_PERCAKAPAN_KONTEKS = 10;

let cacheApriori = null;
let cacheAprioriWaktu = 0;
const CACHE_APRIORI_TTL = 5 * 60 * 1000;

// Simpan socket instance agar bisa dipakai dari API luar
let sockInstance = null;
let isConnected = false;
let lastQrCode = null;
let jumlahReconnect = 0;
const MAX_RECONNECT = 10;
const DELAY_RECONNECT = 5000;

// ============================================================
// SYSTEM PROMPT
// ============================================================
const SYSTEM_PROMPT = `Anda adalah asisten virtual representatif dari PT 4Putra Vertex Aviary, sebuah usaha penangkaran burung paruh bengkok premium milik Syafrudin di Surabaya Barat.

ATURAN KETAT:
1. Anda HANYA boleh merespons topik tentang 5 spesies burung ini:
   - African Grey (Anakan & Dewasa)
   - BNG Macaw (Anakan & Dewasa)
   - Sun Conure (Anakan & Dewasa)
   - Monk Parakeet (Anakan & Dewasa)
   - Indian Ring Neck / IRN (Anakan & Dewasa)

2. Informasi fase ANAKAN: pakan loloh, pengaturan suhu, perawatan khusus anakan
3. Informasi fase DEWASA: diet biji/pelet, perilaku mandiri, perawatan umum

4. Jika ditanya tentang topik DI LUAR 5 spesies di atas atau di luar konteks burung eksotis, belokkan percakapan kembali ke inventaris 4Putra.

5. JIKA PELANGGAN INGIN MEMBELI ATAU BERTANYA HARGA:
   - JANGAN PERNAH menyebutkan harga spesifik (contoh: Rp 2.500.000)
   - JANGAN PERNAH membuat harga sendiri atau mengarang harga
   - Harga hanya ada di sistem inventaris, bukan di AI
   - Arahkan pelanggan untuk mengetik "2" untuk melihat harga dan stok terbaru
   - Atau arahkan mengetik "5" untuk langsung ke menu pembayaran

6. JANGAN PERNAH membuat URL, link, atau kode QRIS sendiri
7. JANGAN PERNAH membuat invoice atau nomor rekening fiktif
8. Semua pembayaran ditangani oleh sistem Midtrans secara otomatis

9. Jika pelanggan ingin membayar atau ketik "bayar", arahkan ke menu pembayaran dengan mengetik "5"

10. JIKA PELANGGAN MENANYAKAN PROSES PEMBAYARAN:
    - Jangan klaim pembayaran sudah berhasil atau sedang diproses
    - Arahkan ke menu pembayaran dengan mengetik "5"
    - Sistem akan otomatis membuat QRIS dan mengarahkan ke Midtrans

Gaya bahasa: Panggil pelanggan dengan 'Kak'. Profesional, cerdas, solutif, komunikatif, ilmiah berbasis data hobi, dan ramah. Jawab singkat, padat, dan langsung pada intinya. Jangan bertele-tele.`;

// ============================================================
// MENU STATIS (konstan, tidak digenerate AI)
// ============================================================
const MENU_STATIS = `Selamat datang di *PT 4Putra Vertex Aviary* 🦜
Terima kasih sudah menghubungi kami. Kami adalah penangkaran burung paruh bengkok premium di Surabaya Barat.

Untuk memudahkan layanan, silakan pilih menu di bawah ini dengan mengetikkan angkanya:

1️⃣ *Konsultasi AI* — Tanya jawab seputar perawatan burung
2️⃣ *Lihat Inventaris* — Daftar burung & harga tersedia
3️⃣ *Hubungi Admin* — Bicara langsung dengan admin kami
4️⃣ *Riwayat Transaksi* — Lihat pembelian sebelumnya
5️⃣ *Bayar (QRIS)* — Pembayaran via QRIS

Atau langsung ketik pertanyaan Kakak di sini, nanti asisten AI kami yang akan menjawab! 🦜`;

// Daftar keyword yang dianggap sebagai sapaan/pembuka menu
const KEYWORD_SAPAAN = ['menu', 'halo', 'hai', 'hi', 'hey', 'helo', 'selamat pagi', 'selamat siang', 'selamat sore', 'selamat malam', 'pagi', 'siang', 'sore', 'malam', 'mulai', 'start', 'help', 'bantuan'];

// ============================================================
// FUNGSI HELPER: Sapaan berdasarkan waktu Indonesia (WIB)
// ============================================================
function dapatkanSapaanWaktu() {
    const sekarang = new Date();
    const jamWIB = new Date(sekarang.toLocaleString('en-US', { timeZone: 'Asia/Jakarta' })).getHours();

    if (jamWIB >= 4 && jamWIB < 11) return { sapaan: 'Selamat Pagi', emoji: '🌅', period: 'pagi' };
    if (jamWIB >= 11 && jamWIB < 15) return { sapaan: 'Selamat Siang', emoji: '☀️', period: 'siang' };
    if (jamWIB >= 15 && jamWIB < 18) return { sapaan: 'Selamat Sore', emoji: '🌇', period: 'sore' };
    return { sapaan: 'Selamat Malam', emoji: '🌙', period: 'malam' };
}

// ============================================================
// HYBRID: Groq AI buat sapaan + menu statis digabung
// ============================================================
async function buatSapaanHybrid(namaPelanggan) {
    const waktu = dapatkanSapaanWaktu();
    const namaDepan = namaPelanggan.split(' ')[0];

    const systemPrompt = `Anda adalah asisten virtual untuk PT 4Putra Vertex Aviary. Tugas Anda HANYA membuat satu paragraf sapaan pembuka (maksimal 2 kalimat).
Aturan wajib:
1. Sapa pengguna dengan 'Kak ${namaDepan}'.
2. Sesuaikan ucapan dengan waktu: ${waktu.sapaan} (WIB).
3. Akhiri sapaan dengan kalimat transisi yang mengarahkan pengguna untuk memilih menu di bawah.
4. DILARANG membuat daftar menu atau menulis hal lain di luar sapaan pembuka.
5. Gunakan emoji yang sesuai.`;

    try {
        const respon = await groq.chat.completions.create({
            messages: [
                { role: 'system', content: systemPrompt },
                { role: 'user', content: 'sapa saya' },
            ],
            model: GROQ_MODEL,
            temperature: 0.7,
            reasoning_effort: 'low',
            max_completion_tokens: 800,
        });
        const sapaanAI = respon.choices[0].message.content.trim();
        // Gabungkan sapaan AI + menu statis
        return sapaanAI + '\n\n' + MENU_STATIS;
    } catch (e) {
        // Fallback jika Groq gagal
        return `${waktu.sapaan}, Kak ${namaDepan}! 👋 Senang dapat terhubung dengan Kakak hari ini.\n\n` + MENU_STATIS;
    }
}

// ============================================================
// FUNGSI BANTUAN
// ============================================================

async function dapatkanPelanggan(remoteJid, pushName = '') {
    // Ekstrak nomor dari JID
    let nomorWa = '';
    let jidType = '';

    if (remoteJid.endsWith('@s.whatsapp.net')) {
        nomorWa = remoteJid.replace('@s.whatsapp.net', '');
        jidType = 'phone';
    } else if (remoteJid.endsWith('@lid')) {
        nomorWa = remoteJid.replace('@lid', '');
        jidType = 'lid';
    } else {
        nomorWa = remoteJid;
        jidType = 'unknown';
    }

    // Cari pelanggan berdasarkan full JID
    let pelanggan = await queryOne('SELECT id, nomor_wa, nama, sesi_aktif, riwayat_konteks, pesan_terakhir FROM pelanggan WHERE nomor_wa = ?', [remoteJid]);

    // Jika tidak ada, coba cari berdasarkan nomor saja (untuk migrasi dari format lama)
    if (!pelanggan) {
        pelanggan = await queryOne('SELECT id, nomor_wa, nama, sesi_aktif, riwayat_konteks, pesan_terakhir FROM pelanggan WHERE nomor_wa = ? OR nomor_wa LIKE ?',
            [nomorWa, nomorWa + '@%']);
    }

    const isNew = !pelanggan;

    if (!pelanggan) {
        const id = await insert(
            'INSERT INTO pelanggan (nomor_wa, nama, sesi_aktif, pesan_terakhir) VALUES (?, ?, ?, NOW())',
            [remoteJid, pushName || null, 'menu']
        );
        pelanggan = {
            id,
            nomor_wa: remoteJid,
            nama: pushName || null,
            sesi_aktif: 'menu',
            riwayat_konteks: null,
            pesan_terakhir: new Date()
        };
    } else {
        // Update JID jika berubah
        if (pelanggan.nomor_wa !== remoteJid) {
            await update('UPDATE pelanggan SET nomor_wa = ? WHERE id = ?', [remoteJid, pelanggan.id]);
            pelanggan.nomor_wa = remoteJid;
        }
        // Update nama jika kosong atau pushName tersedia (update terus agar nama selalu fresh)
        if (pushName && pushName !== pelanggan.nama) {
            await update('UPDATE pelanggan SET nama = ? WHERE id = ?', [pushName, pelanggan.id]);
            pelanggan.nama = pushName;
        }
    }

    return { pelanggan, isNew, nomorWa, jidType };
}

async function cekRateLimit(pelangganId) {
    const pelanggan = await queryOne('SELECT pesan_terakhir FROM pelanggan WHERE id = ?', [pelangganId]);
    if (!pelanggan || !pelanggan.pesan_terakhir) return false;

    const selisih = Date.now() - new Date(pelanggan.pesan_terakhir).getTime();
    return selisih < RATE_LIMIT_MS;
}

async function simpanPercakapan(pelangganId, pesanPengirim, pesanBalasan, sumber, extra = {}) {
    const { replyToId = null, mediaUrl = null, mediaType = null, isForwarded = false, waMessageId = null } = extra;
    await insert(
        'INSERT INTO percakapan (pelanggan_id, wa_message_id, pesan_pengirim, pesan_balasan, sumber_balasan, reply_to_id, media_url, media_type, is_forwarded, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())',
        [pelangganId, waMessageId, pesanPengirim, pesanBalasan, sumber, replyToId, mediaUrl, mediaType, isForwarded]
    );
}

async function dapatkanRiwayatPercakapan(pelangganId, limit = JUMLAH_PERCAKAPAN_KONTEKS) {
    // LIMIT wajib inline (parseInt) — prepared statement mengirim LIMIT ? sebagai string, ditolak TiDB
    return await query(
        `SELECT pesan_pengirim, pesan_balasan FROM percakapan WHERE pelanggan_id = ? ORDER BY created_at DESC LIMIT ${parseInt(limit) || 10}`,
        [pelangganId]
    );
}

async function dapatkanInventaris() {
    return await query('SELECT id, nama_spesies, fase, harga, stok, deskripsi FROM inventaris_burung WHERE aktif = 1 AND stok > 0 ORDER BY nama_spesies, fase');
}

// Ambil riwayat transaksi pelanggan
async function dapatkanRiwayatTransaksi(pelangganId) {
    return await query(
        `SELECT t.id, t.pelanggan_id, t.inventaris_id, t.nominal_dp, t.total_harga, t.quantity, t.status, t.midtrans_order_id, t.created_at, i.nama_spesies, i.fase, i.harga
         FROM transaksi_chatbot t
         LEFT JOIN inventaris_burung i ON t.inventaris_id = i.id
         WHERE t.pelanggan_id = ?
         ORDER BY t.created_at DESC
         LIMIT 10`,
        [pelangganId]
    );
}

// Ambil rekomendasi berdasarkan pembelian sebelumnya
async function dapatkanRekomendasiBerdasarkanPembelian(pelangganId) {
    // Ambil item yang pernah dibeli
    const pembelian = await query(
        `SELECT DISTINCT i.nama_spesies
         FROM transaksi_chatbot t
         JOIN inventaris_burung i ON t.inventaris_id = i.id
         WHERE t.pelanggan_id = ? AND t.status = 'paid'`,
        [pelangganId]
    );

    if (pembelian.length === 0) return null;

    const hasilApriori = dapatkanHasilApriori();
    const rekomendasi = [];

    for (const item of pembelian) {
        const aturanCocok = hasilApriori.aturanKuatFinal.find(rule =>
            rule.antecedents.toLowerCase().includes(item.nama_spesies.toLowerCase())
        );

        if (aturanCocok) {
            rekomendasi.push({
                dibeli: item.nama_spesies,
                rekomendasi: aturanCocok.consequents,
                confidence: aturanCocok.confidence,
            });
        }
    }

    return rekomendasi.length > 0 ? rekomendasi : null;
}

// Rekomendasi natural via Groq dari aturan asosiasi Apriori
async function buatRekomendasiAprioriGroq(pelangganId, namaPelanggan) {
    const pembelian = await query(
        `SELECT DISTINCT i.nama_spesies
         FROM transaksi_chatbot t
         JOIN inventaris_burung i ON t.inventaris_id = i.id
         WHERE t.pelanggan_id = ? AND t.status = 'paid'`,
        [pelangganId]
    );
    if (pembelian.length === 0) return null;

    const aturan = dapatkanHasilApriori().aturanKuatFinal.filter(rule =>
        pembelian.some(p => rule.antecedents.toLowerCase().includes(p.nama_spesies.toLowerCase()))
    );
    if (aturan.length === 0) return null;

    const daftarAturan = aturan
        .map(r => `- ${r.antecedents} -> ${r.consequents} (confidence ${r.confidence})`)
        .join('\n');

    const systemPrompt = `Anda asisten virtual penangkaran PT 4Putra Vertex Aviary (burung paruh bengkok premium). Pelanggan bernama ${namaPelanggan}.

Berikut data aturan asosiasi dari riwayat pembelian pelanggan:
${daftarAturan}

Tugas Anda: Terjemahkan aturan asosiasi ini menjadi rekomendasi pembelian burung yang natural dan ramah tanpa menyebutkan istilah teknis matematis (dilarang menyebut: support, confidence, aturan asosiasi, data mining, atau istilah statistik lain).

Gaya: panggil pelanggan 'Kak', maksimal 4 kalimat, hangat dan persuasif. Akhiri dengan ajakan mengetik *2* untuk melihat stok atau *5* untuk membeli.`;

    try {
        const respon = await groq.chat.completions.create({
            messages: [
                { role: 'system', content: systemPrompt },
                { role: 'user', content: 'Buat rekomendasi pembeliannya' },
            ],
            model: GROQ_MODEL,
            temperature: 0.7,
            reasoning_effort: 'low',
            max_completion_tokens: 800,
        });
        return respon.choices[0].message.content.trim();
    } catch (e) {
        console.error('[APRIORI-GROQ] Gagal generate rekomendasi:', e.message);
        return null;
    }
}

// Format riwayat transaksi untuk WhatsApp
function formatRiwayatTransaksi(daftar) {
    if (!daftar || daftar.length === 0) {
        return '📦 *Riwayat Transaksi*\n\nBelum ada transaksi, Kak. Yuk mulai koleksi burung impian! 🦜';
    }

    let teks = '📦 *Riwayat Transaksi Kakak*\n\n';

    for (const trx of daftar) {
        const harga = Number(trx.total_harga || trx.harga).toLocaleString('id-ID');
        const status = trx.status === 'paid' ? '✅ Lunas' : (trx.status === 'pending' ? '⏳ Pending' : '❌ ' + trx.status);
        const fase = trx.fase === 'anakan' ? 'Baby' : 'Dewasa';
        const tanggal = new Date(trx.created_at).toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

        teks += `*${trx.nama_spesies || 'Produk'}* (${fase})\n`;
        teks += `   Tanggal: ${tanggal}\n`;
        teks += `   Nominal: Rp ${harga}\n`;
        teks += `   Status: ${status}\n\n`;
    }

    return teks;
}

// Buat rekomendasi berdasarkan Apriori dengan elaborasi natural
function buatRekomendasiNatural(aturan, burungDibeli) {
    const deskripsi = {
        'african grey': {
            sifat: 'terkenal sangat pintar, bisa meniru banyak kata-kata, dan suka berinteraksi',
            cocok: 'teman ngobrol di rumah',
        },
        'bng macaw': {
            sifat: 'sangat cantik dengan warna mencolok, energik, dan setia pada pemiliknya',
            cocok: 'pajangan hidup yang memukau',
        },
        'sun conure': {
            sifat: 'sangat aktif, ceria, dan suka bermain. Warna kuning-oranye yang memikat',
            cocok: 'teman bermain yang menghibur',
        },
        'monk parakeet': {
            sifat: 'jinak, suka bersosial, dan bisa diajak main. Mudah beradaptasi',
            cocok: 'teman di rumah yang ramah',
        },
        'indian ring neck': {
            sifat: 'pandai bicara, aktif, dan punya bulu hijau yang elegan',
            cocok: 'teman ngobrol yang seru',
        },
    };

    const namaBurung = aturan.consequents.toLowerCase();
    const info = deskripsi[namaBurung] || { sifat: 'sangat istimewa', cocok: 'koleksi premium' };

    const saran = `Kak, berdasarkan data pembelian kami, pelanggan yang suka *${burungDibeli}* biasanya juga tertarik dengan *${aturan.consequents}*! 

Burung ini ${info.sifat}, jadi ${info.cocok}. Kalau dipasangkan dengan ${burungDibeli}, pasti jadi koleksi yang sempurna di rumah Kakak! 🦜

Tingkat keyakinan: *${aturan.confidence}*

Mau lihat detail harga atau stoknya? Ketik *2* ya!`;

    return saran;
}

async function buatNotifikasi(tipe, judul, isi, pelangganId = null) {
    await insert(
        'INSERT INTO notifikasi_admins (tipe, judul, isi, pelanggan_id, dibaca, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())',
        [tipe, judul, isi, pelangganId]
    );
}

function dapatkanHasilApriori() {
    const sekarang = Date.now();
    if (!cacheApriori || (sekarang - cacheAprioriWaktu) > CACHE_APRIORI_TTL) {
        cacheApriori = eksekusiAprioriLengkap();
        cacheAprioriWaktu = sekarang;
    }
    return cacheApriori;
}

function formatInventaris(daftar) {
    if (!daftar || daftar.length === 0) {
        return 'Maaf Kak, saat ini stok burung sedang kosong. Silakan cek kembali nanti ya.';
    }

    let teks = '*📋 DAFTAR BURUNG TERSEDIA — PT 4Putra Vertex Aviary*\n\n';

    daftar.forEach((item, idx) => {
        const harga = Number(item.harga).toLocaleString('id-ID');
        const fase = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
        teks += `${idx + 1}. *${item.nama_spesies}* (${fase}) — Rp ${harga} (Stok: ${item.stok})\n`;
    });

    teks += '\nKetik *nomor* burung untuk lanjut ke pembayaran, atau ketik *menu* untuk kembali ya, Kak! 🦜';
    return teks;
}

// Ekstrak nomor dari JID untuk display
function getNomorDisplay(remoteJid) {
    if (!remoteJid) return '';
    return remoteJid.replace('@s.whatsapp.net', '').replace('@lid', '');
}

// Format nomor HP untuk display
function formatNomorHp(nomor) {
    if (!nomor) return '';
    // Hapus suffix jika ada
    let clean = nomor.replace('@s.whatsapp.net', '').replace('@lid', '');
    // Format: 628xxx -> +62 8xxx
    if (clean.startsWith('62') && clean.length > 10) {
        return '+' + clean.substring(0, 2) + ' ' + clean.substring(2);
    }
    return clean;
}

// ============================================================
// FUNGSI PEMBAYARAN MIDTRANS QRIS
// ============================================================
async function buatPembayaranQRIS(pelangganId, inventarisId, namaSpesies, fase, harga, remoteJid, quantity = 1) {
    try {
        // Buat order ID unik
        const orderId = `4PUTRA-${Date.now()}-${pelangganId}`;
        const totalHarga = harga * quantity;
        console.log(`[QRIS] Membuat pembayaran: OrderID=${orderId}, Produk=${namaSpesies}, Qty=${quantity}, Total=${totalHarga}`);

        // Simpan transaksi ke database dengan inventaris_id dan quantity
        const transaksiId = await insert(
            `INSERT INTO transaksi_chatbot (pelanggan_id, inventaris_id, nominal_dp, total_harga, quantity, status, midtrans_order_id, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())`,
            [pelangganId, inventarisId, totalHarga, totalHarga, quantity, orderId]
        );
        console.log(`[QRIS] Transaksi tersimpan: ID=${transaksiId}`);

        // Buat transaksi Midtrans
        const pelanggan = await queryOne('SELECT id, nomor_wa, nama FROM pelanggan WHERE id = ?', [pelangganId]);
        const namaDisplay = pelanggan?.nama || 'Pelanggan';
        const nomorDisplay = getNomorDisplay(pelanggan?.nomor_wa || '');

        console.log(`[QRIS] Memanggil Midtrans Core API (payment_type: qris)...`);
        const faseLabel = fase === 'anakan' ? 'Baby' : 'Dewasa';
        const midtransResult = await createQrisCharge(orderId, totalHarga, {
            nama: namaDisplay,
            nomor: nomorDisplay,
            nama_produk: `${namaSpesies} ${faseLabel} x${quantity}`,
        }, [
            {
                id: orderId,
                price: Math.round(harga),
                quantity: quantity,
                name: `${namaSpesies} ${faseLabel}`,
            },
        ]);
        console.log(`[QRIS] Midtrans response: status=${midtransResult.status_code}, qr=${!!midtransResult.qrImageUrl}`);

        // Simpan URL QR (fallback ke redirect_url Snap jika gambar tidak tersedia)
        const urlQr = midtransResult.qrImageUrl || midtransResult.redirect_url;
        await update(
            'UPDATE transaksi_chatbot SET qr_url = ? WHERE id = ?',
            [urlQr, transaksiId]
        );

        const caption = `✅ *Pesanan Berhasil Dibuat!*\n\n` +
            `📦 *Detail Pesanan:*\n` +
            `• Produk: ${namaSpesies} (${fase === 'anakan' ? 'Baby' : 'Dewasa'})\n` +
            `• Jumlah: ${quantity} ekor\n` +
            `• Harga satuan: Rp ${Number(harga).toLocaleString('id-ID')}\n` +
            `• *Total: Rp ${Number(totalHarga).toLocaleString('id-ID')}*\n` +
            `• Order ID: ${orderId}\n\n` +
            `💳 Scan QR di atas dengan aplikasi apa pun yang mendukung QRIS (GoPay, OVO, DANA, m-banking).\n` +
            `⏰ Pembayaran berlaku 24 jam. Konfirmasi pembayaran akan otomatis terkirim di chat ini.`;

        // Kirim QRIS sebagai gambar (image message)
        let terkirimGambar = false;
        if (midtransResult.qrImageUrl) {
            try {
                const qrBuffer = await unduhGambarQr(midtransResult.qrImageUrl);
                await sockInstance.sendMessage(remoteJid, { image: qrBuffer, caption });
                terkirimGambar = true;
            } catch (e) {
                console.error('[QRIS] Gagal kirim gambar QR, fallback ke teks:', e.message);
            }
        }

        if (!terkirimGambar) {
            await sockInstance.sendMessage(remoteJid, {
                text: caption + (midtransResult.redirect_url ? `\n\n💳 Atau bayar via link berikut:\n${midtransResult.redirect_url}` : '')
            });
        }

        return { success: true, orderId, transaksiId };
    } catch (error) {
        console.error('[QRIS] Gagal membuat pembayaran:', error.message);
        if (error.response) {
            console.error('[QRIS] Response error:', JSON.stringify(error.response.data));
        }
        await sockInstance.sendMessage(remoteJid, {
            text: 'Maaf Kak, terjadi kesalahan saat membuat pembayaran. Silakan coba lagi atau hubungi admin dengan ketik *3*.'
        });
        return { success: false, error: error.message };
    }
}

// ============================================================
// DAFTAR INVENTARIS & CHECKOUT (state inventory / checkout)
// ============================================================
function simpanKonteksDaftar(pelanggan, tujuanState) {
    return dapatkanInventaris().then(inventaris => {
        if (!inventaris || inventaris.length === 0) return null;
        return update(
            'UPDATE pelanggan SET sesi_aktif = ?, riwayat_konteks = ? WHERE id = ?',
            [tujuanState, JSON.stringify({
                action: 'pilih_bayar',
                items: inventaris.map(i => ({ id: i.id, nama: i.nama_spesies, fase: i.fase, harga: i.harga }))
            }), pelanggan.id]
        ).then(() => inventaris);
    });
}

// Menu 2: daftar stok bernomor (state: inventory)
async function kirimDaftarInventaris(pelanggan, remoteJid, teksMasuk) {
    const inventaris = await simpanKonteksDaftar(pelanggan, 'inventory');
    pelanggan.sesi_aktif = 'inventory';

    let balasan;
    if (!inventaris) {
        balasan = 'Maaf Kak, saat ini stok burung sedang kosong. Silakan cek kembali nanti ya.';
    } else {
        balasan = formatInventaris(inventaris);
        balasan += '\n\n🛒 Ingin langsung membeli? Ketik *5* untuk menu pembayaran, atau ketik *3* untuk terhubung dengan admin.';
    }

    await sockInstance.sendMessage(remoteJid, { text: balasan });
    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
}

// Menu 5: daftar pembayaran QRIS bernomor (state: checkout)
async function kirimDaftarCheckout(pelanggan, remoteJid, teksMasuk) {
    const inventaris = await simpanKonteksDaftar(pelanggan, 'checkout');
    pelanggan.sesi_aktif = 'checkout';

    let balasan;
    if (!inventaris) {
        balasan = 'Maaf Kak, saat ini belum ada burung yang tersedia. Silakan cek kembali nanti ya.';
    } else {
        balasan = '💳 *Pembayaran QRIS — PT 4Putra Vertex Aviary*\n\nSilakan pilih burung yang ingin Kakak beli:\n\n';
        inventaris.forEach((item, idx) => {
            const harga = Number(item.harga).toLocaleString('id-ID');
            const fase = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
            balasan += `${idx + 1}. *${item.nama_spesies}* (${fase}) — Rp ${harga}\n`;
        });
        balasan += '\nKetik *nomor* burung yang ingin dibeli (contoh: 1)';
    }

    await sockInstance.sendMessage(remoteJid, { text: balasan });
    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
}

// ============================================================
// EXPORT: Kirim pesan dari admin dashboard (dipanggil oleh index.js)
// ============================================================
export async function kirimPesanKePelanggan(nomorWa, pesan) {
    if (!sockInstance) {
        throw new Error('Bot WhatsApp belum terhubung');
    }

    // Pastikan JID lengkap
    let jid = nomorWa;
    if (!jid.includes('@')) {
        jid = nomorWa + '@s.whatsapp.net';
    }

    await sockInstance.sendMessage(jid, { text: pesan });
    return true;
}

// ============================================================
// FUNGSI UTAMA BOT
// ============================================================
async function hubungkanKeWhatsApp() {
    const { state, saveCreds } = await useMultiFileAuthState(join(__dirname, 'auth_info'));

    const sock = makeWASocket({
        logger: pino({ level: 'silent' }),
        auth: state,
        printQRInTerminal: false,
    });

    sockInstance = sock;

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            lastQrCode = qr;
            const host = `localhost:${process.env.PORT || 3001}`;
            console.log('==================================================');
            console.log('SCAN QR CODE DI BROWSER:');
            console.log(`http://${host}/qr`);
            console.log('==================================================');
        }

        if (connection === 'close') {
            isConnected = false;
            const harusKonekUlang = (lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut);
            console.log('Koneksi terputus:', lastDisconnect?.error || 'Unknown', '. Konek ulang:', harusKonekUlang);
            if (harusKonekUlang) {
                jumlahReconnect++;
                if (jumlahReconnect > MAX_RECONNECT) {
                    console.log(`[!] Gagal konek setelah ${MAX_RECONNECT} percobaan. Bot berhenti. Jalankan ulang: node whatsapp.js`);
                    process.exit(1);
                }
                console.log(`[i] Konek ulang ${jumlahReconnect}/${MAX_RECONNECT} dalam ${DELAY_RECONNECT/1000} detik...`);
                setTimeout(() => hubungkanKeWhatsApp(), DELAY_RECONNECT);
            }
        } else if (connection === 'open') {
            isConnected = true;
            jumlahReconnect = 0;
            lastQrCode = null;
            console.log('==================================================');
            console.log('BOT WHATSAPP 4PUTRA VERTEX AVIARY AKTIF!');
            console.log('==================================================');
        }
    });

    sock.ev.on('creds.update', saveCreds);

    // ============================================================
    // EVENT HAPUS PESAN DARI PELANGGAN (delete for everyone)
    // ============================================================
    sock.ev.on('messages.delete', async (deletion) => {
        try {
            // deletion bisa berupa { keys: [...] } atau { jid, ... }
            const keys = deletion.keys || [];
            for (const key of keys) {
                if (key.fromMe) continue; // Skip pesan dari bot
                const waMsgId = key.id;
                const remoteJid = key.remoteJid;

                // Cari pesan di database
                const chat = await queryOne(
                    'SELECT id, pelanggan_id, pesan_pengirim, media_url FROM percakapan WHERE wa_message_id = ? LIMIT 1',
                    [waMsgId]
                );
                if (!chat) continue;

                // Update pesan jadi "telah dihapus"
                await update(
                    'UPDATE percakapan SET pesan_pengirim = ?, media_url = NULL, media_type = NULL, deleted_for_pelanggan = true WHERE id = ?',
                    ['🚫 Pesan ini telah dihapus oleh pengirim', chat.id]
                );

                // Hapus media file jika ada
                if (chat.media_url) {
                    const relativePath = chat.media_url.replace('/storage/', '');
                    const fullPath = join(__dirname, '../storage/app/public/', relativePath);
                    if (fs.existsSync(fullPath)) {
                        try { fs.unlinkSync(fullPath); } catch (e) {}
                    }
                }

                console.log(`[DELETE] Pesan ${waMsgId} dari ${remoteJid} dihapus untuk semua`);
            }
        } catch (err) {
            console.error('Gagal handle message delete:', err.message);
        }
    });

    // ============================================================
    // EVENT PENERIMAAN PESAN MASUK
    // ============================================================
    sock.ev.on('messages.upsert', async (m) => {
        try {
            const msg = m.messages[0];
            if (!msg.message || msg.key.fromMe) return;

            const remoteJid = msg.key.remoteJid;

            // Skip grup dan broadcast
            if (remoteJid.includes('@g.us') || remoteJid === 'status@broadcast') return;

            // Ambil nama dari pushName (nama WhatsApp user)
            const pushName = msg.pushName || '';

            // Simpan wa_message_id dari pesan masuk
            const incomingWaId = msg.key.id || null;

            const tipePesan = Object.keys(msg.message)[0];

            // Deteksi pesan diteruskan (forwarded)
            // Baileys v7: forwardingScore > 0 ATAU contextInfo.isForwarded
            // Reply juga punya contextInfo (quotedMessage) — forwardingScore di dalamnya milik pesan
            // yang dikutip, bukan pesan ini → jangan dianggap forwarded.
            let isForwarded = false;
            const msgContextInfo = msg.message.extendedTextMessage?.contextInfo ||
                msg.message.imageMessage?.contextInfo ||
                msg.message.videoMessage?.contextInfo ||
                msg.message.documentMessage?.contextInfo ||
                msg.message.audioMessage?.contextInfo;
            const isReplyPesan = !!(msgContextInfo?.stanzaId && msgContextInfo?.quotedMessage);
            if (!isReplyPesan) {
                if (msgContextInfo?.forwardingScore > 0) isForwarded = true;
                if (msgContextInfo?.isForwarded) isForwarded = true;
            }

            // Deteksi reply context
            let replyToId = null;
            if (msgContextInfo?.stanzaId) {
                // Cari pesan asli berdasarkan wa_message_id
                const originalMsg = await queryOne('SELECT id FROM percakapan WHERE wa_message_id = ? LIMIT 1', [msgContextInfo.stanzaId]);
                if (originalMsg) replyToId = originalMsg.id;
            }

            // Ekstrak teks pesan dan media
            let teksMasuk = '';
            let mediaUrl = null;
            let mediaType = null;

            if (tipePesan === 'conversation') {
                teksMasuk = msg.message.conversation;
            } else if (tipePesan === 'extendedTextMessage') {
                teksMasuk = msg.message.extendedTextMessage.text;
            } else if (tipePesan === 'imageMessage') {
                teksMasuk = msg.message.imageMessage.caption || '[Image]';
                mediaType = 'image';
            } else if (tipePesan === 'videoMessage') {
                teksMasuk = msg.message.videoMessage.caption || '[Video]';
                mediaType = 'video';
            } else if (tipePesan === 'documentMessage') {
                teksMasuk = msg.message.documentMessage.fileName || '[Document]';
                mediaType = 'document';
            } else if (tipePesan === 'audioMessage') {
                teksMasuk = '[Audio]';
                mediaType = 'audio';
            } else if (tipePesan === 'buttonsResponseMessage') {
                teksMasuk = msg.message.buttonsResponseMessage.selectedButtonId;
            } else if (tipePesan === 'listResponseMessage') {
                teksMasuk = msg.message.listResponseMessage.singleSelectReply.selectedRowId;
            }

            // Download media jika ada
            if (mediaType && sockInstance) {
                try {
                    const buffer = await sockInstance.downloadMediaMessage(msg, 'buffer');
                    const ext = mediaType === 'image' ? 'jpg' : (mediaType === 'video' ? 'mp4' : (mediaType === 'audio' ? 'mp3' : 'bin'));
                    const filename = `wa_${Date.now()}_${Math.random().toString(36).substr(2, 6)}.${ext}`;
                    const filepath = join(__dirname, '../storage/app/public/chat-media', filename);
                    fs.mkdirSync(join(__dirname, '../storage/app/public/chat-media'), { recursive: true });
                    fs.writeFileSync(filepath, buffer);
                    mediaUrl = `/storage/chat-media/${filename}`;
                } catch (dlErr) {
                    console.error('Gagal download media:', dlErr.message);
                }
            }

            teksMasuk = teksMasuk.trim();
            if (!teksMasuk && !mediaUrl) return;

            const teksMasukLower = teksMasuk.toLowerCase();

            // ============================================================
            // LANGKAH 1: Ambil data pelanggan
            // ============================================================
            const { pelanggan, isNew, nomorWa, jidType } = await dapatkanPelanggan(remoteJid, pushName);

            // ============================================================
            // LANGKAH 2: Untuk user baru → set session awal
            // ============================================================
            if (isNew) {
                await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['menu', pelanggan.id]);
                pelanggan.sesi_aktif = 'menu';
            }

            // ============================================================
            // LANGKAH 3: Cek rate limit (skip untuk tombol/keyword penting)
            // ============================================================
            const isImportantKeyword = [...KEYWORD_SAPAAN, 'menu_ai', 'menu_inventaris', 'menu_admin', 'menu_transaksi', 'menu_bayar', '1', '2', '3', '4', '5', 'admin', 'transaksi', 'riwayat', 'bayar', 'qris'].includes(teksMasukLower);

            if (!isImportantKeyword && await cekRateLimit(pelanggan.id)) {
                return;
            }

            // Update pesan terakhir
            await update('UPDATE pelanggan SET pesan_terakhir = NOW() WHERE id = ?', [pelanggan.id]);

            // ============================================================
            // LANGKAH 4: Deteksi keyword Apriori & Niat Pembelian
            // ============================================================
            const varietasBurung = {
                'baby afgrey': 'Baby Afgrey',
                'baby sun conure': 'Baby Sun conure',
                'baby monk': 'Baby Monk',
                'baby bng macaw': 'Baby BNG Macaw',
                'baby indian ring neck': 'Baby Indian Ring Neck',
                'afgrey': 'Afgrey',
                'sun conure': 'Sun Conure',
                'monk': 'Monk',
                'bng macaw': 'BNG Macaw',
                'indian ring neck': 'Indian Ring Neck',
            };

            let burungTerdeteksi = null;
            for (const keyword in varietasBurung) {
                if (teksMasukLower.includes(keyword)) {
                    burungTerdeteksi = varietasBurung[keyword];
                    break;
                }
            }

            // TAMBAHAN: Deteksi intensi eksplisit untuk transaksi
            const niatBeli = ['beli', 'pesan', 'order', 'mau ambil', 'bayar'].some(kata => teksMasukLower.includes(kata));

            if (burungTerdeteksi && pelanggan.sesi_aktif !== 'human') {

                if (niatBeli) {
                    // BYPASS APRIORI: Pelanggan menyatakan ingin beli → langsung daftar pembayaran (state checkout)
                    await kirimDaftarCheckout(pelanggan, remoteJid, teksMasuk);
                    return;
                } else {
                    // TAMPILKAN REKOMENDASI: Jika hanya menyebut nama burung (misal: "Afgrey")
                    const hasilApriori = dapatkanHasilApriori();

                    const aturanCocok = hasilApriori.aturanKuatFinal.find(rule =>
                        rule.antecedents.toLowerCase().includes(burungTerdeteksi.toLowerCase())
                    );

                    let balasan = '';
                    if (aturanCocok) {
                        // Elaborasi natural dan modifikasi Call-to-Action
                        balasan = buatRekomendasiNatural(aturanCocok, burungTerdeteksi);
                        balasan = balasan.replace('Ketik *2* ya!', 'Ketik *2* untuk cek stok, atau ketik *5* jika ingin langsung membeli ya! 🛒');
                    } else {
                        balasan = `Halo Kak! Terima kasih sudah tertarik dengan *${burungTerdeteksi}*.\n\n` +
                                  `Untuk info harga silakan ketik *2*, atau jika ingin langsung membeli ketik *5* ya!`;
                    }

                    await sock.sendPresenceUpdate('composing', remoteJid);
                    setTimeout(async () => {
                        await sock.sendMessage(remoteJid, { text: balasan });
                    }, 1000);

                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'apriori');
                    return; // Hentikan alur di sini agar tidak memicu menu lain
                }
            }

            // ============================================================
            // LANGKAH 5: Routing berdasarkan sesi aktif (state machine)
            // ============================================================

            // --- MODE MENU ---
            if (pelanggan.sesi_aktif === 'menu') {
                // Handle "menu" → tampilkan menu lagi tanpa ganti state
                if (teksMasukLower === 'menu') {
                    const namaPelanggan = pelanggan.nama || 'Kak';
                    const pesanUtuh = await buatSapaanHybrid(namaPelanggan);
                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                // Handle button response
                if (teksMasuk === 'menu_ai' || teksMasuk === '1') {
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['ai', pelanggan.id]);
                    const balasan = 'Baik Kak! Mode konsultasi AI sudah aktif. Silakan tanyakan seputar perawatan burung, nutrisi, atau info lainnya tentang 5 spesies unggulan kami.\n\nKetik *menu* untuk kembali ke menu utama.';
                    await sock.sendMessage(remoteJid, { text: balasan });
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    return;
                }

                if (teksMasuk === 'menu_inventaris' || teksMasuk === '2') {
                    await kirimDaftarInventaris(pelanggan, remoteJid, teksMasuk);
                    return;
                }

                if (teksMasuk === 'menu_admin' || teksMasuk === '3' || teksMasukLower === 'admin') {
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['human', pelanggan.id]);
                    const balasan = 'Baik Kak! Admin kami akan segera merespons. Mohon tunggu sebentar ya. Pesan Kakak sudah kami teruskan ke admin.\n\nKetik *menu* untuk kembali ke menu otomatis.';
                    await sock.sendMessage(remoteJid, { text: balasan });

                    // Notifikasi realtime ke Admin Dashboard via Firebase
                    await kirimHandoffKeFirebase(pelanggan.id, { nama: pelanggan.nama, nomor: nomorWa });

                    await buatNotifikasi(
                        'permintaan_manual',
                        'Pelanggan minta bicara dengan admin',
                        `Pelanggan ${pelanggan.nama || nomorWa} (${nomorWa}) meminta untuk bicara dengan admin.`,
                        pelanggan.id
                    );

                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    return;
                }

                if (teksMasuk === 'menu_transaksi' || teksMasuk === '4' || teksMasukLower === 'transaksi' || teksMasukLower === 'riwayat') {
                    const transaksi = await dapatkanRiwayatTransaksi(pelanggan.id);
                    let balasan = formatRiwayatTransaksi(transaksi);

                    // Rekomendasi Apriori diterjemahkan Groq AI (fallback: template statis)
                    const namaPelanggan = pelanggan.nama || 'Kak';
                    const rekomAi = await buatRekomendasiAprioriGroq(pelanggan.id, namaPelanggan);
                    if (rekomAi) {
                        balasan += `\n\n💡 *Rekomendasi untuk Kakak:*\n${rekomAi}`;
                    } else {
                        const rekomendasi = await dapatkanRekomendasiBerdasarkanPembelian(pelanggan.id);
                        if (rekomendasi) {
                            balasan += '\n\n💡 *Rekomendasi untuk Kakak:*\n';
                            for (const rec of rekomendasi) {
                                balasan += `\nBerdasarkan pembelian *${rec.dibeli}*, kami sarankan juga melirik *${rec.rekomendasi}* (Keyakinan: ${rec.confidence})`;
                            }
                        }
                    }

                    balasan += '\n\nKetik *menu* untuk kembali ke menu utama.';
                    await sock.sendMessage(remoteJid, { text: balasan });
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'apriori');
                    return;
                }

                if (teksMasuk === 'menu_bayar' || teksMasuk === '5' || teksMasukLower === 'bayar' || teksMasukLower === 'qris') {
                    await kirimDaftarCheckout(pelanggan, remoteJid, teksMasuk);
                    return;
                }

                // Input tidak dikenali → tampilkan menu lagi
                const namaPelanggan = pelanggan.nama || 'Kak';
                const pesanUtuh = await buatSapaanHybrid(namaPelanggan);
                await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                return;
            }

            // --- MODE INVENTARIS & CHECKOUT (alur beli bersama) ---
            if (pelanggan.sesi_aktif === 'inventory' || pelanggan.sesi_aktif === 'checkout') {
                if (teksMasukLower === 'menu') {
                    await update('UPDATE pelanggan SET sesi_aktif = ?, riwayat_konteks = NULL WHERE id = ?', ['menu', pelanggan.id]);
                    const pesanUtuh = await buatSapaanHybrid(pelanggan.nama || 'Kak');
                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                let konteks = null;
                try {
                    konteks = typeof pelanggan.riwayat_konteks === 'string'
                        ? JSON.parse(pelanggan.riwayat_konteks)
                        : pelanggan.riwayat_konteks;
                } catch (e) { konteks = null; }

                // STEP 2: pilih nomor burung → minta quantity
                if (konteks?.action === 'pilih_bayar' && /^\d+$/.test(teksMasuk)) {
                    const pilihan = parseInt(teksMasuk) - 1;
                    const items = konteks.items || [];

                    if (pilihan >= 0 && pilihan < items.length) {
                        const item = items[pilihan];
                        const faseLabel = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
                        const hargaFormatted = Number(item.harga).toLocaleString('id-ID');

                        await update('UPDATE pelanggan SET riwayat_konteks = ? WHERE id = ?',
                            [JSON.stringify({
                                action: 'pilih_quantity',
                                item: { id: item.id, nama: item.nama, fase: item.fase, harga: item.harga }
                            }), pelanggan.id]);

                        const balasan = `Kakak pilih *${item.nama}* (${faseLabel}) — Rp ${hargaFormatted}/ekor\n\n` +
                            `Mau beli berapa ekor? Ketik jumlahnya ya, Kak (contoh: 1, 2, 3)`;

                        await sockInstance.sendMessage(remoteJid, { text: balasan });
                        await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    } else {
                        await sockInstance.sendMessage(remoteJid, { text: 'Pilihan tidak valid. Silakan ketik nomor yang tersedia ya, Kak.' });
                    }
                    return;
                }

                // STEP 3: quantity → buat QRIS
                if (konteks?.action === 'pilih_quantity' && /^\d+$/.test(teksMasuk)) {
                    const quantity = parseInt(teksMasuk);

                    if (quantity < 1 || quantity > 100) {
                        await sockInstance.sendMessage(remoteJid, { text: 'Jumlah tidak valid. Silakan ketik angka antara 1 sampai 100 ya, Kak.' });
                        return;
                    }

                    const item = konteks.item;

                    const stokItem = await queryOne('SELECT stok FROM inventaris_burung WHERE id = ?', [item.id]);
                    if (stokItem && quantity > stokItem.stok) {
                        await sockInstance.sendMessage(remoteJid, {
                            text: `Maaf Kak, stok *${item.nama}* (${item.fase === 'anakan' ? 'Baby' : 'Dewasa'}) hanya tersisa ${stokItem.stok} ekor. Silakan kurangi jumlahnya ya.`
                        });
                        return;
                    }

                    await buatPembayaranQRIS(
                        pelanggan.id,
                        item.id,
                        item.nama,
                        item.fase,
                        item.harga,
                        remoteJid,
                        quantity
                    );

                    // Transaksi dibuat → kembali ke menu
                    await update('UPDATE pelanggan SET sesi_aktif = ?, riwayat_konteks = NULL WHERE id = ?', ['menu', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, '[QRIS dikirim]', 'menu');
                    return;
                }

                // Input tidak dikenal → ulangi daftar sesuai state
                if (pelanggan.sesi_aktif === 'inventory') {
                    await kirimDaftarInventaris(pelanggan, remoteJid, teksMasuk);
                } else {
                    await kirimDaftarCheckout(pelanggan, remoteJid, teksMasuk);
                }
                return;
            }

            // --- MODE HUMAN (admin takeover, merge eks-'manual') ---
            if (pelanggan.sesi_aktif === 'human') {
                if (teksMasukLower === 'menu') {
                    await hapusHandoffFirebase(pelanggan.id);
                    await update('UPDATE pelanggan SET sesi_aktif = ?, riwayat_konteks = NULL WHERE id = ?', ['menu', pelanggan.id]);
                    const pesanUtuh = await buatSapaanHybrid(pelanggan.nama || 'Kak');

                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                // Simpan pesan saja, bot tidak membalas
                await simpanPercakapan(pelanggan.id, teksMasuk, null, 'human', { replyToId, mediaUrl, mediaType, isForwarded, waMessageId: incomingWaId });
                await buatNotifikasi(
                    'pesan_masuk',
                    'Pesan dari pelanggan (admin takeover)',
                    `${pelanggan.nama || nomorWa}: ${teksMasuk}`,
                    pelanggan.id
                );
                return;
            }

            // --- MODE AI ---
            if (pelanggan.sesi_aktif === 'ai') {
                if (teksMasukLower === 'menu') {
                    const namaPelanggan = pelanggan.nama || 'Kak';
                    const pesanUtuh = await buatSapaanHybrid(namaPelanggan);

                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['menu', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                // Di mode AI, angka 1-5 tidak diproses sebagai menu → perlakukan sebagai pertanyaan AI

                const riwayat = await dapatkanRiwayatPercakapan(pelanggan.id);

                // Buat system prompt dinamis dengan nama pelanggan
                const namaPelanggan = pelanggan.nama || 'Kak';
                const namaDepan = namaPelanggan.split(' ')[0];
                const systemPromptDinamis = SYSTEM_PROMPT + `\n\nINFORMASI PELANGGAN:\n- Nama pelanggan: ${namaPelanggan}\n- Selalu panggil dengan "Kak ${namaDepan}" atau "${namaDepan}"\n- Jika pelanggan menyapa (misal: "selamat pagi", "halo", "hai"), balas sapaan tersebut dengan ramah dan sebutkan nama mereka\n- Contoh: "Selamat malam, Kak ${namaDepan}! Ada yang bisa saya bantu hari ini?"`;

                const messages = [{ role: 'system', content: systemPromptDinamis }];

                for (const perc of riwayat.reverse()) {
                    if (perc.pesan_pengirim) {
                        messages.push({ role: 'user', content: perc.pesan_pengirim });
                    }
                    if (perc.pesan_balasan) {
                        messages.push({ role: 'assistant', content: perc.pesan_balasan });
                    }
                }

                messages.push({ role: 'user', content: teksMasuk });

                const responGroq = await groq.chat.completions.create({
                    messages,
                    model: GROQ_MODEL,
                    temperature: 0.7,
                    reasoning_effort: 'low',
                    max_completion_tokens: 1000,
                });

                const balasanGroq = responGroq.choices[0].message.content;

                await sock.sendPresenceUpdate('composing', remoteJid);
                setTimeout(async () => {
                    await sock.sendMessage(remoteJid, { text: balasanGroq });
                }, 1000);

                await simpanPercakapan(pelanggan.id, teksMasuk, balasanGroq, 'groq_ai');
                return;
            }

            // Fallback: state tidak dikenal → kembali ke menu
            await update('UPDATE pelanggan SET sesi_aktif = ?, riwayat_konteks = NULL WHERE id = ?', ['menu', pelanggan.id]);
            const pesanMenu = await buatSapaanHybrid(pelanggan.nama || 'Kak');
            await sockInstance.sendMessage(remoteJid, { text: pesanMenu });

        } catch (error) {
            console.error('Gagal memproses pesan:', error.message);
        }
    });
}

// ============================================================
// HTTP SERVER UNTUK MENERIMA REQUEST DARI INDEX.JS / LARAVEL
// ============================================================
const apiApp = express();
apiApp.use(express.json());

// Endpoint untuk kirim pesan dari admin dashboard
apiApp.post('/send', async (req, res) => {
    try {
        const { jid, pesan, quoted_wa_id } = req.body;

        if (!jid || !pesan) {
            return res.status(400).json({ error: 'jid dan pesan wajib diisi' });
        }

        if (!sockInstance || !isConnected) {
            return res.status(503).json({ error: 'Bot WhatsApp belum terhubung' });
        }

        let fullJid = jid;
        if (!fullJid.includes('@')) {
            fullJid = jid + '@s.whatsapp.net';
        }

        const msgOptions = { text: pesan };

        // Tambah quoted message jika ada
        if (quoted_wa_id) {
            try {
                // Cari pesan asli dari store
                const msgs = await sockInstance.store?.loadMessages?.(fullJid, 100);
                const quotedMsg = msgs?.find(m => m.key.id === quoted_wa_id);
                if (quotedMsg) {
                    msgOptions.quoted = quotedMsg;
                }
            } catch (e) {
                // Fallback: buat quoted message manual
                msgOptions.quoted = {
                    key: { id: quoted_wa_id, remoteJid: fullJid, fromMe: false },
                    message: { conversation: pesan },
                };
            }
        }

        const result = await sockInstance.sendMessage(fullJid, msgOptions);
        const waMessageId = result?.key?.id || null;

        res.json({ status: 'OK', sent_to: fullJid, wa_message_id: waMessageId });
    } catch (error) {
        console.error('Gagal kirim pesan via Baileys:', error.message);
        res.status(500).json({ error: error.message });
    }
});

// Endpoint untuk kirim media dari admin dashboard
import multer from 'multer';
const upload = multer({ dest: '/tmp/chat-upload/' });

apiApp.post('/send-media', upload.single('file'), async (req, res) => {
    try {
        const { jid, caption, media_type, quoted_wa_id } = req.body;

        if (!jid || !req.file) {
            return res.status(400).json({ error: 'jid dan file wajib diisi' });
        }

        if (!sockInstance || !isConnected) {
            return res.status(503).json({ error: 'Bot WhatsApp belum terhubung' });
        }

        let fullJid = jid;
        if (!fullJid.includes('@')) {
            fullJid = jid + '@s.whatsapp.net';
        }

        const fileBuffer = fs.readFileSync(req.file.path);
        const captionText = caption && caption !== '[Image]' && caption !== '[Video]' && caption !== '[Document]' ? caption : '';

        let msgOptions;
        if (media_type === 'image') {
            msgOptions = { image: fileBuffer, caption: captionText };
        } else if (media_type === 'video') {
            msgOptions = { video: fileBuffer, caption: captionText };
        } else if (media_type === 'audio') {
            msgOptions = { audio: fileBuffer, mimetype: 'audio/mpeg' };
        } else {
            msgOptions = { document: fileBuffer, mimetype: req.file.mimetype, fileName: req.file.originalname };
        }

        // Tambah quoted message jika ada
        if (quoted_wa_id) {
            try {
                const msgs = await sockInstance.store?.loadMessages?.(fullJid, 100);
                const quotedMsg = msgs?.find(m => m.key.id === quoted_wa_id);
                if (quotedMsg) msgOptions.quoted = quotedMsg;
            } catch (e) {
                msgOptions.quoted = {
                    key: { id: quoted_wa_id, remoteJid: fullJid, fromMe: false },
                    message: { conversation: caption || '' },
                };
            }
        }

        const result = await sockInstance.sendMessage(fullJid, msgOptions);
        const waMessageId = result?.key?.id || null;

        // Hapus file temporary
        fs.unlinkSync(req.file.path);

        res.json({ status: 'OK', sent_to: fullJid, wa_message_id: waMessageId });
    } catch (error) {
        console.error('Gagal kirim media via Baileys:', error.message);
        res.status(500).json({ error: error.message });
    }
});

// Endpoint untuk hapus pesan (delete for everyone)
apiApp.post('/delete', async (req, res) => {
    try {
        const { jid, wa_message_id } = req.body;

        if (!jid || !wa_message_id) {
            return res.status(400).json({ error: 'jid dan wa_message_id wajib diisi' });
        }

        if (!sockInstance || !isConnected) {
            return res.status(503).json({ error: 'Bot WhatsApp belum terhubung' });
        }

        let fullJid = jid;
        if (!fullJid.includes('@')) {
            fullJid = jid + '@s.whatsapp.net';
        }

        await sockInstance.sendMessage(fullJid, {
            delete: {
                id: wa_message_id,
                remoteJid: fullJid,
                fromMe: true,
            }
        });

        res.json({ status: 'OK' });
    } catch (error) {
        console.error('Gagal hapus pesan via Baileys:', error.message);
        res.status(500).json({ error: error.message });
    }
});

apiApp.get('/health', (req, res) => {
    res.json({
        status: 'OK',
        bot_connected: isConnected,
    });
});

// Halaman QR code untuk scan WhatsApp
apiApp.get('/qr', (req, res) => {
    // Bot sudah terhubung
    if (isConnected && !lastQrCode) {
        return res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>QR WhatsApp</title>
<meta http-equiv="refresh" content="10">
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.3);}
h2{margin-bottom:8px;}p{color:#a0a0a0;}.ok{background:#22c55e20;color:#22c55e;padding:8px 16px;border-radius:8px;display:inline-block;}</style></head><body>
<div class="box"><h2>WhatsApp Bot</h2><p class="ok">Bot sudah terhubung dan aktif!</p>
<p>Tidak perlu scan QR lagi.</p></div></body></html>`);
    }

    // Menunggu QR (bot reconnecting)
    if (!lastQrCode) {
        return res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>QR WhatsApp</title>
<meta http-equiv="refresh" content="5">
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.3);}
h2{margin-bottom:8px;}p{color:#a0a0a0;}.wait{background:#f59e0b20;color:#f59e0b;padding:8px 16px;border-radius:8px;display:inline-block;}</style></head><body>
<div class="box"><h2>WhatsApp Bot</h2><p class="wait">Bot sedang reconnecting...</p>
<p>Halaman auto-refresh setiap 5 detik. Tunggu sampai QR muncul.</p></div></body></html>`);
    }

    const qrUrl = `https://api.qrserver.com/v1/create-qr-code/?size=300x300&data=${encodeURIComponent(lastQrCode)}`;

    res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Scan QR WhatsApp</title>
<meta http-equiv="refresh" content="30">
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;box-shadow:0 4px 20px rgba(0,0,0,0.3);}
h2{margin-bottom:8px;}p{color:#a0a0a0;margin-bottom:20px;}
img{border-radius:12px;background:#fff;padding:12px;}
.status{margin-top:16px;padding:8px 16px;border-radius:8px;display:inline-block;font-size:14px;}
.waiting{background:#f59e0b20;color:#f59e0b;}</style></head><body>
<div class="box"><h2>Scan QR WhatsApp</h2><p>Buka WhatsApp di HP → Settings → Linked Devices → Link a Device</p>
<img src="${qrUrl}" alt="QR Code" width="300" height="300">
<p class="status waiting">Menunggu scan... (auto-refresh 30 detik)</p></div></body></html>`);
});

const API_PORT = process.env.PORT || 3001;

// Reset auth_info untuk generate QR baru
async function resetAuth() {
    const authPath = join(__dirname, 'auth_info');
    if (!fs.existsSync(authPath)) {
        return { success: true };
    }

    // Tutup socket untuk putus koneksi WhatsApp
    if (sockInstance) {
        try {
            sockInstance.ev.removeAllListeners();
            if (sockInstance.ws) sockInstance.ws.close();
            sockInstance.end();
        } catch (e) {
            console.log('Warning saat tutup socket:', e.message);
        }
        sockInstance = null;
        isConnected = false;
    }

    // Tunggu socket benar-benar tertutup supaya file handle lepas
    await new Promise(r => setTimeout(r, 5000));

    // Hapus isi auth_info satu per satu (bukan rmdir, untuk hindari EBUSY)
    const maxAttempts = 3;
    for (let attempt = 1; attempt <= maxAttempts; attempt++) {
        try {
            const files = fs.readdirSync(authPath);
            for (const file of files) {
                const filePath = join(authPath, file);
                try {
                    fs.unlinkSync(filePath);
                } catch (e) {
                    // Jika unlink gagal (EBUSY), overwrite dengan data kosong
                    try { fs.writeFileSync(filePath, '', 'utf-8'); } catch (_) {}
                }
            }
            // Coba hapus directory kosong
            try { fs.rmSync(authPath, { recursive: true, force: true }); } catch (_) {}
            console.log(`auth_info berhasil di-reset (percobaan ${attempt})`);
            return { success: true };
        } catch (e) {
            console.log(`Percobaan ${attempt} gagal:`, e.message);
            if (attempt < maxAttempts) await new Promise(r => setTimeout(r, 3000));
        }
    }

    return { success: false, error: 'Gagal reset auth_info setelah 3 percobaan' };
}

apiApp.post('/reset', async (req, res) => {
    const result = await resetAuth();
    if (result.success) {
        res.json({ status: 'OK', message: 'Auth dihapus. Bot akan restart.' });
        setTimeout(() => process.exit(0), 1000);
    } else {
        res.status(500).json({ error: result.error });
    }
});

apiApp.get('/reset', async (req, res) => {
    const authPath = join(__dirname, 'auth_info');
    const hasAuth = fs.existsSync(authPath);

    if (req.query.confirm === '1') {
        const result = await resetAuth();
        if (result.success) {
            res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reset Berhasil</title>
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;}</style></head><body>
<div class="box"><h2>Auth Dihapus!</h2><p>Bot sedang restart...</p>
<p>Buka <a href="/qr" style="color:#22c55e">/qr</a> setelah restart untuk scan QR baru.</p>
<script>setTimeout(()=>window.location.href='/qr',8000)</script></div></body></html>`);
            setTimeout(() => process.exit(0), 1000);
        } else {
            res.status(500).send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Error</title>
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;max-width:400px;}</style></head><body>
<div class="box"><h2 style="color:#ef4444">Error</h2><p>${result.error}</p>
<a href="/reset" style="color:#22c55e">Coba lagi</a></div></body></html>`);
        }
        return;
    }

    res.send(`<!DOCTYPE html><html><head><meta charset="utf-8"><title>Reset WhatsApp Auth</title>
<style>body{font-family:sans-serif;display:flex;justify-content:center;align-items:center;min-height:100vh;margin:0;background:#1a1a2e;color:#fff;}
.box{text-align:center;padding:40px;background:#16213e;border-radius:16px;max-width:400px;}
h2{margin-bottom:8px;}p{color:#a0a0a0;margin-bottom:20px;}
a.btn{display:inline-block;padding:12px 24px;background:#ef4444;color:#fff;text-decoration:none;border-radius:8px;font-weight:bold;}
a.btn:hover{background:#dc2626;}
.ok{color:#22c55e;}</style></head><body>
<div class="box"><h2>Reset WhatsApp Auth</h2>
<p>Auth saat ini: ${hasAuth ? '<span style="color:#f59e0b">Ada (credential lama)</span>' : '<span class="ok">Tidak ada</span>'}</p>
<p>Ini akan menghapus credential WhatsApp dan memaksa generate QR baru. Bot akan restart.</p>
<a class="btn" href="/reset?confirm=1">Ya, Reset Sekarang</a></div></body></html>`);
});

// Status detail bot
apiApp.get('/status', (req, res) => {
    const authExists = fs.existsSync(join(__dirname, 'auth_info'));
    res.json({
        status: 'OK',
        bot_connected: isConnected,
        has_auth: authExists,
        has_qr: !!lastQrCode,
        port: API_PORT,
    });
});

apiApp.listen(API_PORT, '0.0.0.0', () => {
    console.log(`Baileys API berjalan di port ${API_PORT}`);

    // Mulai koneksi WhatsApp SETELAH Express server aktif
    // Jika gagal, Express tetap jalan sehingga /qr dan /status bisa diakses
    hubungkanKeWhatsApp().catch(err => {
        console.error('Gagal koneksi WhatsApp, retry dalam 15 detik:', err.message);
        setTimeout(() => {
            hubungkanKeWhatsApp().catch(e => console.error('Retry gagal:', e.message));
        }, 15000);
    });
});

// Global error handler agar process tidak crash
process.on('uncaughtException', (err) => {
    console.error('Uncaught Exception:', err.message);
});
process.on('unhandledRejection', (err) => {
    console.error('Unhandled Rejection:', err?.message || err);
});
