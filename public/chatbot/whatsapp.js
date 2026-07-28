import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';

const __dirname = dirname(fileURLToPath(import.meta.url));
config({ path: join(__dirname, '../../.env') });
config({ path: join(__dirname, '.env'), override: true });
import { makeWASocket, useMultiFileAuthState, DisconnectReason } from '@whiskeysockets/baileys';
import pino from 'pino';
import qrcode from 'qrcode-terminal';
import { Groq } from 'groq-sdk';
import express from 'express';
import { eksekusiAprioriLengkap } from './apriori.js';
import { query, queryOne, insert, update } from './db.js';
import { createTransaction } from './midtrans.js';

// ============================================================
// KONFIGURASI
// ============================================================
const GROQ_API_KEY = process.env.GROQ_API_KEY;
const groq = new Groq({ apiKey: GROQ_API_KEY });
const RATE_LIMIT_MS = 2000;
const JUMLAH_PERCAKAPAN_KONTEKS = 10;

let cacheApriori = null;
let cacheAprioriWaktu = 0;
const CACHE_APRIORI_TTL = 5 * 60 * 1000;

// Simpan socket instance agar bisa dipakai dari API luar
let sockInstance = null;
let isConnected = false;

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
            model: 'llama-3.1-8b-instant',
            temperature: 0.7,
            max_tokens: 100,
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
// KIRIM INTERACTIVE BUTTONS (JIKA SUPPORT)
// ============================================================
async function kirimMenuDenganButtons(remoteJid, teks) {
    try {
        await sockInstance.sendMessage(remoteJid, {
            text: teks,
            footer: 'PT 4Putra Vertex Aviary — Surabaya Barat',
            buttons: [
                { buttonId: 'menu_ai', buttonText: { displayText: '🤖 Konsultasi AI' }, type: 1 },
                { buttonId: 'menu_inventaris', buttonText: { displayText: '📋 Lihat Inventaris' }, type: 1 },
                { buttonId: 'menu_admin', buttonText: { displayText: '👤 Hubungi Admin' }, type: 1 },
                { buttonId: 'menu_transaksi', buttonText: { displayText: '📦 Riwayat Transaksi' }, type: 1 },
                { buttonId: 'menu_bayar', buttonText: { displayText: '💳 Bayar (QRIS)' }, type: 1 },
            ],
            headerType: 1,
        });
    } catch (err) {
        console.log('Buttons tidak support, kirim teks biasa');
        await sockInstance.sendMessage(remoteJid, { text: teks });
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
            [remoteJid, pushName || null, 'awal']
        );
        pelanggan = {
            id,
            nomor_wa: remoteJid,
            nama: pushName || null,
            sesi_aktif: 'awal',
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

async function simpanPercakapan(pelangganId, pesanPengirim, pesanBalasan, sumber) {
    await insert(
        'INSERT INTO percakapan (pelanggan_id, pesan_pengirim, pesan_balasan, sumber_balasan, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
        [pelangganId, pesanPengirim, pesanBalasan, sumber]
    );
}

async function dapatkanRiwayatPercakapan(pelangganId, limit = JUMLAH_PERCAKAPAN_KONTEKS) {
    return await query(
        'SELECT pesan_pengirim, pesan_balasan FROM percakapan WHERE pelanggan_id = ? ORDER BY created_at DESC LIMIT ?',
        [pelangganId, limit]
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
    let spesiesSekarang = '';

    for (const item of daftar) {
        if (item.nama_spesies !== spesiesSekarang) {
            if (spesiesSekarang) teks += '\n';
            teks += `*${item.nama_spesies}*\n`;
            spesiesSekarang = item.nama_spesies;
        }

        const harga = Number(item.harga).toLocaleString('id-ID');
        const fase = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
        teks += `  • ${fase}: Rp ${harga} (Stok: ${item.stok})\n`;
    }

    teks += '\nKetik *3* atau ketik *admin* untuk terhubung langsung dengan admin ya, Kak! 🦜';
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

        console.log(`[QRIS] Memanggil Midtrans API...`);
        const faseLabel = fase === 'anakan' ? 'Baby' : 'Dewasa';
        const midtransResult = await createTransaction(orderId, totalHarga, {
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
        console.log(`[QRIS] Midtrans response:`, JSON.stringify(midtransResult));

        // Update QR URL ke transaksi
        await update(
            'UPDATE transaksi_chatbot SET qr_url = ? WHERE id = ?',
            [midtransResult.redirect_url, transaksiId]
        );

        // Kirim pesan dengan link pembayaran
        const pesan = `✅ *Pesanan Berhasil Dibuat!*\n\n` +
            `📦 *Detail Pesanan:*\n` +
            `• Produk: ${namaSpesies} (${fase === 'anakan' ? 'Baby' : 'Dewasa'})\n` +
            `• Jumlah: ${quantity} ekor\n` +
            `• Harga satuan: Rp ${Number(harga).toLocaleString('id-ID')}\n` +
            `• Total: Rp ${Number(totalHarga).toLocaleString('id-ID')}\n` +
            `• Order ID: ${orderId}\n\n` +
            `💳 *Cara Pembayaran:*\n` +
            `Klik link di bawah untuk membayar dengan QRIS:\n` +
            `${midtransResult.redirect_url}\n\n` +
            `⏰ Pembayaran berlaku selama 24 jam.\n` +
            `Setelah pembayaran berhasil, admin kami akan segera memproses pesanan Kakak.`;

        await sockInstance.sendMessage(remoteJid, { text: pesan });

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
    const { state, saveCreds } = await useMultiFileAuthState('auth_info');

    const sock = makeWASocket({
        logger: pino({ level: 'silent' }),
        auth: state,
        printQRInTerminal: false,
    });

    sockInstance = sock;

    sock.ev.on('connection.update', (update) => {
        const { connection, lastDisconnect, qr } = update;

        if (qr) {
            console.log('==================================================');
            console.log('SILAKAN SCAN QR CODE DI BAWAH INI:');
            console.log('==================================================');
            qrcode.generate(qr, { small: true });
        }

        if (connection === 'close') {
            isConnected = false;
            const harusKonekUlang = (lastDisconnect?.error?.output?.statusCode !== DisconnectReason.loggedOut);
            console.log('Koneksi terputus:', lastDisconnect?.error || 'Unknown', '. Konek ulang:', harusKonekUlang);
            if (harusKonekUlang) hubungkanKeWhatsApp();
        } else if (connection === 'open') {
            isConnected = true;
            console.log('==================================================');
            console.log('BOT WHATSAPP 4PUTRA VERTEX AVIARY AKTIF!');
            console.log('==================================================');
        }
    });

    sock.ev.on('creds.update', saveCreds);

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

            const tipePesan = Object.keys(msg.message)[0];

            // Ekstrak teks pesan
            let teksMasuk = '';
            if (tipePesan === 'conversation') {
                teksMasuk = msg.message.conversation;
            } else if (tipePesan === 'extendedTextMessage') {
                teksMasuk = msg.message.extendedTextMessage.text;
            } else if (tipePesan === 'buttonsResponseMessage') {
                // Handle button response
                teksMasuk = msg.message.buttonsResponseMessage.selectedButtonId;
            } else if (tipePesan === 'listResponseMessage') {
                // Handle list response
                teksMasuk = msg.message.listResponseMessage.singleSelectReply.selectedRowId;
            }

            teksMasuk = teksMasuk.trim();
            if (!teksMasuk) return;

            const teksMasukLower = teksMasuk.toLowerCase();

            // ============================================================
            // LANGKAH 1: Ambil data pelanggan
            // ============================================================
            const { pelanggan, isNew, nomorWa, jidType } = await dapatkanPelanggan(remoteJid, pushName);

            // ============================================================
            // LANGKAH 2: Untuk user baru → set session awal
            // ============================================================
            if (isNew) {
                await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['awal', pelanggan.id]);
                pelanggan.sesi_aktif = 'awal';
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

            if (burungTerdeteksi && pelanggan.sesi_aktif !== 'manual' && pelanggan.sesi_aktif !== 'human') {
                
                if (niatBeli) {
                    // BYPASS APRIORI: Jika pelanggan menyatakan ingin beli (misal: "Saya ingin membeli afgrey")
                    // Ubah input menjadi trigger menu pembayaran agar diproses di LANGKAH 5
                    teksMasuk = '5';
                    teksMasukLower = '5';
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
            // LANGKAH 4.5: Cek konteks pembayaran
            // ============================================================
            if (pelanggan.riwayat_konteks) {
                try {
                    const konteks = typeof pelanggan.riwayat_konteks === 'string'
                        ? JSON.parse(pelanggan.riwayat_konteks)
                        : pelanggan.riwayat_konteks;

                    // STEP 2: User memilih nomor burung → minta quantity
                    if (konteks.action === 'pilih_bayar' && /^\d+$/.test(teksMasuk)) {
                        const pilihan = parseInt(teksMasuk) - 1;
                        const items = konteks.items;

                        if (pilihan >= 0 && pilihan < items.length) {
                            const item = items[pilihan];
                            const faseLabel = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
                            const hargaFormatted = Number(item.harga).toLocaleString('id-ID');

                            // Simpan konteks baru: pilih_quantity
                            await update('UPDATE pelanggan SET riwayat_konteks = ? WHERE id = ?',
                                [JSON.stringify({
                                    action: 'pilih_quantity',
                                    item: { id: item.id, nama: item.nama, fase: item.fase, harga: item.harga }
                                }), pelanggan.id]);

                            const balasan = `Kakak pilih *${item.nama}* (${faseLabel}) — Rp ${hargaFormatted}/ekor\n\n` +
                                `Mau beli berapa ekor? Ketik jumlahnya ya, Kak (contoh: 1, 2, 3)`;

                            await sockInstance.sendMessage(remoteJid, { text: balasan });
                            await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                            return;
                        } else {
                            await sockInstance.sendMessage(remoteJid, { text: 'Pilihan tidak valid. Silakan ketik nomor yang tersedia ya, Kak.' });
                            return;
                        }
                    }

                    // STEP 3: User memilih quantity → buat pembayaran QRIS
                    if (konteks.action === 'pilih_quantity' && /^\d+$/.test(teksMasuk)) {
                        const quantity = parseInt(teksMasuk);

                        if (quantity < 1 || quantity > 100) {
                            await sockInstance.sendMessage(remoteJid, { text: 'Jumlah tidak valid. Silakan ketik angka antara 1 sampai 100 ya, Kak.' });
                            return;
                        }

                        const item = konteks.item;

                        // Cek stok
                        const stokItem = await queryOne('SELECT stok FROM inventaris_burung WHERE id = ?', [item.id]);
                        if (stokItem && quantity > stokItem.stok) {
                            await sockInstance.sendMessage(remoteJid, {
                                text: `Maaf Kak, stok *${item.nama}* (${item.fase === 'anakan' ? 'Baby' : 'Dewasa'}) hanya tersisa ${stokItem.stok} ekor. Silakan kurangi jumlahnya ya.`
                            });
                            return;
                        }

                        // Buat pembayaran QRIS
                        await buatPembayaranQRIS(
                            pelanggan.id,
                            item.id,
                            item.nama,
                            item.fase,
                            item.harga,
                            remoteJid,
                            quantity
                        );

                        // Hapus konteks
                        await update('UPDATE pelanggan SET riwayat_konteks = NULL WHERE id = ?', [pelanggan.id]);
                        await simpanPercakapan(pelanggan.id, teksMasuk, null, 'menu');
                        return;
                    }
                } catch (e) {
                    // Konteks tidak valid, lanjutkan
                }
            }

            // ============================================================
            // LANGKAH 5: Routing berdasarkan sesi aktif
            // ============================================================

            // --- MODE AWAL (user harus sapa dulu untuk buka menu) ---
            if (pelanggan.sesi_aktif === 'awal') {
                const isSapaan = KEYWORD_SAPAAN.some(kata => teksMasukLower.includes(kata));

                if (isSapaan) {
                    const namaPelanggan = pelanggan.nama || 'Kak';

                    // Hybrid: Groq AI sapaan + menu statis digabung
                    const pesanUtuh = await buatSapaanHybrid(namaPelanggan);

                    await sock.sendPresenceUpdate('composing', remoteJid);
                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });

                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['menu', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                } else {
                    const waktu = dapatkanSapaanWaktu();
                    const balasan = `${waktu.sapaan} Kak! 👋 Untuk memulai, silakan ketik *menu* atau sapaan seperti *halo* ya.`;
                    await sockInstance.sendMessage(remoteJid, { text: balasan });
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'system');
                    return;
                }
            }

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
                    const inventaris = await dapatkanInventaris();
                    let balasan = formatInventaris(inventaris);
                    // Tambahkan saran untuk membeli
                    balasan += '\n\n🛒 Apakah Kakak tertarik untuk membeli? Ketik *5* untuk langsung ke menu pembayaran, atau ketik *3* untuk terhubung dengan admin.';
                    await sock.sendMessage(remoteJid, { text: balasan });
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['awal', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    return;
                }

                if (teksMasuk === 'menu_admin' || teksMasuk === '3' || teksMasukLower === 'admin') {
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['manual', pelanggan.id]);
                    const balasan = 'Baik Kak! Admin kami akan segera merespons. Mohon tunggu sebentar ya. Pesan Kakak sudah kami teruskan ke admin.\n\nKetik *menu* untuk kembali ke menu otomatis.';
                    await sock.sendMessage(remoteJid, { text: balasan });

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

                    const rekomendasi = await dapatkanRekomendasiBerdasarkanPembelian(pelanggan.id);
                    if (rekomendasi) {
                        balasan += '\n\n💡 *Rekomendasi untuk Kakak:*\n';
                        for (const rec of rekomendasi) {
                            balasan += `\nBerdasarkan pembelian *${rec.dibeli}*, kami sarankan juga melirik *${rec.rekomendasi}* (Keyakinan: ${rec.confidence})`;
                        }
                    }

                    balasan += '\n\nKetik *menu* untuk kembali ke menu utama.';
                    await sock.sendMessage(remoteJid, { text: balasan });
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['awal', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    return;
                }

                if (teksMasuk === 'menu_bayar' || teksMasuk === '5' || teksMasukLower === 'bayar' || teksMasukLower === 'qris') {
                    const inventaris = await dapatkanInventaris();
                    if (!inventaris || inventaris.length === 0) {
                        const balasan = 'Maaf Kak, saat ini belum ada burung yang tersedia. Silakan cek kembali nanti ya.';
                        await sock.sendMessage(remoteJid, { text: balasan });
                        await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                        return;
                    }

                    let balasan = '💳 *Pembayaran QRIS — PT 4Putra Vertex Aviary*\n\nSilakan pilih burung yang ingin Kakak beli:\n\n';
                    let nomor = 1;
                    for (const item of inventaris) {
                        const harga = Number(item.harga).toLocaleString('id-ID');
                        const fase = item.fase === 'anakan' ? 'Baby' : 'Dewasa';
                        balasan += `${nomor}. *${item.nama_spesies}* (${fase}) — Rp ${harga}\n`;
                        nomor++;
                    }
                    balasan += `\nKetik *nomor* burung yang ingin dibeli (contoh: 1)`;

                    await update('UPDATE pelanggan SET riwayat_konteks = ? WHERE id = ?',
                        [JSON.stringify({ action: 'pilih_bayar', items: inventaris.map(i => ({ id: i.id, nama: i.nama_spesies, fase: i.fase, harga: i.harga })) }), pelanggan.id]);

                    await sock.sendMessage(remoteJid, { text: balasan });
                    await simpanPercakapan(pelanggan.id, teksMasuk, balasan, 'menu');
                    return;
                }

                // Input tidak dikenali → tampilkan menu lagi
                const namaPelanggan = pelanggan.nama || 'Kak';
                const pesanUtuh = await buatSapaanHybrid(namaPelanggan);
                await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                return;
            }

            // --- MODE MANUAL ---
            if (pelanggan.sesi_aktif === 'manual') {
                if (teksMasukLower === 'menu') {
                    const namaPelanggan = pelanggan.nama || 'Kak';
                    const pesanUtuh = await buatSapaanHybrid(namaPelanggan);

                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['menu', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                await simpanPercakapan(pelanggan.id, teksMasuk, null, 'manual');
                await buatNotifikasi(
                    'pesan_masuk',
                    'Pesan dari pelanggan (mode manual)',
                    `${pelanggan.nama || nomorWa}: ${teksMasuk}`,
                    pelanggan.id
                );

                const balasan = 'Pesan Kakak sudah diteruskan ke admin. Admin akan membalas secepatnya ya! Ketik *menu* untuk kembali ke menu otomatis.';
                await sock.sendMessage(remoteJid, { text: balasan });
                return;
            }

            // --- MODE HUMAN (admin takeover) ---
            if (pelanggan.sesi_aktif === 'human') {
                if (teksMasukLower === 'menu') {
                    const namaPelanggan = pelanggan.nama || 'Kak';
                    const pesanUtuh = await buatSapaanHybrid(namaPelanggan);

                    await sockInstance.sendMessage(remoteJid, { text: pesanUtuh });
                    await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['menu', pelanggan.id]);
                    await simpanPercakapan(pelanggan.id, teksMasuk, pesanUtuh, 'menu');
                    return;
                }

                // Simpan pesan saja, bot tidak membalas
                await simpanPercakapan(pelanggan.id, teksMasuk, null, 'human');
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
                    model: 'llama-3.1-8b-instant',
                    temperature: 0.7,
                    max_tokens: 300,
                });

                const balasanGroq = responGroq.choices[0].message.content;

                await sock.sendPresenceUpdate('composing', remoteJid);
                setTimeout(async () => {
                    await sock.sendMessage(remoteJid, { text: balasanGroq });
                }, 1000);

                await simpanPercakapan(pelanggan.id, teksMasuk, balasanGroq, 'groq_ai');
                return;
            }

            // Fallback: arahkan ke awal
            await update('UPDATE pelanggan SET sesi_aktif = ? WHERE id = ?', ['awal', pelanggan.id]);
            const balasan = 'Halo Kak! 👋 Untuk memulai, silakan ketik *menu* ya. 😊';
            await sockInstance.sendMessage(remoteJid, { text: balasan });

        } catch (error) {
            console.error('Gagal memproses pesan:', error.message);
        }
    });
}

hubungkanKeWhatsApp();

// ============================================================
// HTTP SERVER UNTUK MENERIMA REQUEST DARI INDEX.JS / LARAVEL
// ============================================================
const apiApp = express();
apiApp.use(express.json());

// Endpoint untuk kirim pesan dari admin dashboard
apiApp.post('/send', async (req, res) => {
    try {
        const { jid, pesan } = req.body;

        if (!jid || !pesan) {
            return res.status(400).json({ error: 'jid dan pesan wajib diisi' });
        }

        if (!sockInstance || !isConnected) {
            return res.status(503).json({ error: 'Bot WhatsApp belum terhubung' });
        }

        // Konversi @lid ke @s.whatsapp.net agar bisa dikirim
        let fullJid = jid;
        if (fullJid.includes('@lid')) {
            fullJid = fullJid.replace('@lid', '@s.whatsapp.net');
        } else if (!fullJid.includes('@')) {
            fullJid = jid + '@s.whatsapp.net';
        }

        await sockInstance.sendMessage(fullJid, { text: pesan });

        res.json({ status: 'OK', sent_to: fullJid });
    } catch (error) {
        console.error('Gagal kirim pesan via Baileys:', error.message);
        res.status(500).json({ error: error.message });
    }
});

apiApp.get('/health', (req, res) => {
    res.json({
        status: 'OK',
        bot_connected: isConnected,
    });
});

const API_PORT = 3001;
apiApp.listen(API_PORT, () => {
    console.log(`Baileys API berjalan di port ${API_PORT}`);
});
