import { dirname, join } from 'path';
import { fileURLToPath } from 'url';
import { config } from 'dotenv';

const __dirname = dirname(fileURLToPath(import.meta.url));
config({ path: join(__dirname, '../../.env') });
config({ path: join(__dirname, '.env'), override: true });

import express from 'express';
import axios from 'axios';
import { query, queryOne, insert, update } from './db.js';
import { verifySignature, getTransactionStatus } from './midtrans.js';

const app = express();
app.use(express.json());

// Konfigurasi Meta Cloud API dari environment variables
const TOKEN_META = process.env.META_TOKEN;
const ID_NOMOR_TELEPON = process.env.META_PHONE_NUMBER_ID;

// ============================================================
// ENDPOINT 1: META CLOUD API WEBHOOK (WhatsApp)
// ============================================================
app.post('/webhook', (req, res) => {
    const body = req.body;

    if (body.object) {
        res.sendStatus(200);

        if (body.entry?.[0]?.changes?.[0]?.value?.messages?.[0]) {
            const nomerWaPelanggan = body.entry[0].changes[0].value.messages[0].from;
            const pesanMasuk = body.entry[0].changes[0].value.messages[0].text.body;

            console.log('Pesan masuk dari', nomerWaPelanggan, ':', pesanMasuk);
            kirimBalasanMeta(nomerWaPelanggan, pesanMasuk);
        } else if (body.entry?.[0]?.changes?.[0]?.value?.statuses?.[0]) {
            const statusMeta = body.entry[0].changes[0].value.statuses[0];
            if (statusMeta.status === 'failed') {
                console.error('PESAN GAGAL:', JSON.stringify(statusMeta.errors, null, 2));
            }
        }
    }
});

// Kirim balasan via Meta Graph API
async function kirimBalasanMeta(nomor, pesan) {
    try {
        await axios.post(
            `https://graph.facebook.com/v18.0/${ID_NOMOR_TELEPON}/messages`,
            {
                messaging_product: 'whatsapp',
                to: nomor,
                text: { body: pesan },
            },
            {
                headers: {
                    'Authorization': `Bearer ${TOKEN_META}`,
                    'Content-Type': 'application/json',
                },
            }
        );
        console.log('Balasan terkirim ke', nomor);
    } catch (error) {
        console.error('Gagal kirim balasan Meta:', error.response?.data || error.message);
    }
}

// ============================================================
// ENDPOINT 2: MIDTRANS WEBHOOK CALLBACK
// ============================================================
app.post('/midtrans/callback', async (req, res) => {
    try {
        const { order_id, status_code, gross_amount, signature_key, transaction_status, transaction_id, payment_type } = req.body;

        // Verifikasi signature
        const isValid = verifySignature(order_id, status_code, gross_amount, signature_key);
        if (!isValid) {
            console.error('Signature Midtrans tidak valid untuk order:', order_id);
            return res.status(403).json({ error: 'Invalid signature' });
        }

        // Cari transaksi di database
        const transaksi = await queryOne('SELECT id, pelanggan_id, inventaris_id, nominal_dp, total_harga, quantity, status, midtrans_order_id, qr_url FROM transaksi_chatbot WHERE midtrans_order_id = ?', [order_id]);
        if (!transaksi) {
            console.error('Transaksi tidak ditemukan:', order_id);
            return res.status(404).json({ error: 'Transaction not found' });
        }

        // Tentukan status berdasarkan transaction_status
        let statusBaru = 'pending';
        if (transaction_status === 'settlement' || transaction_status === 'capture') {
            statusBaru = 'paid';
        } else if (transaction_status === 'expire') {
            statusBaru = 'expired';
        } else if (transaction_status === 'cancel' || transaction_status === 'deny') {
            statusBaru = 'cancelled';
        }

        // Idempotensi: webhook bisa terkirim ulang oleh Midtrans — skip jika status tidak berubah
        if (transaksi.status === statusBaru) {
            console.log('[MIDTRANS] Webhook duplikat diabaikan untuk order:', order_id, 'status:', statusBaru);
            return res.json({ status: 'OK', note: 'duplicate ignored' });
        }

        // Simpan data pembayaran
        await insert(
            'INSERT INTO pembayarans (transaksi_id, midtrans_txn_id, metode, nominal, status, raw_webhook, created_at, updated_at) VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())',
            [transaksi.id, transaction_id, payment_type, gross_amount, transaction_status, JSON.stringify(req.body)]
        );

        // Update status transaksi
        await update('UPDATE transaksi_chatbot SET status = ?, updated_at = NOW() WHERE id = ?', [statusBaru, transaksi.id]);

        // Jika pembayaran berhasil
        if (statusBaru === 'paid') {
            // Ambil data pelanggan
            const pelanggan = await queryOne('SELECT id, nomor_wa, nama FROM pelanggan WHERE id = ?', [transaksi.pelanggan_id]);

            // Kirim notifikasi ke admin
            await insert(
                'INSERT INTO notifikasi_admins (tipe, judul, isi, pelanggan_id, dibaca, created_at, updated_at) VALUES (?, ?, ?, ?, 0, NOW(), NOW())',
                ['pembayaran', 'Pembayaran Diterima!', `Pembayaran dari ${pelanggan?.nama || pelanggan?.nomor_wa} sebesar Rp ${Number(gross_amount).toLocaleString('id-ID')} telah berhasil.`, transaksi.pelanggan_id]
            );

            // Kirim tanda terima ke pelanggan
            if (pelanggan?.nomor_wa) {
                const pesan = `✅ *Pembayaran Berhasil!*\n\n` +
                              `Order ID: ${order_id}\n` +
                              `Nominal: Rp ${Number(gross_amount).toLocaleString('id-ID')}\n` +
                              `Metode: ${payment_type}\n\n` +
                              `Terima kasih telah berbelanja di PT 4Putra Vertex Aviary! Admin kami akan segera menghubungi Kakak untuk pengiriman.`;

                // Coba kirim via Baileys (port 3001) terlebih dahulu
                let sent = false;
                try {
                    const baileysRes = await axios.post('http://127.0.0.1:3001/send', {
                        jid: pelanggan.nomor_wa,
                        pesan: pesan,
                    }, { timeout: 5000 });

                    if (baileysRes.data && baileysRes.data.status === 'OK') {
                        sent = true;
                        console.log('Notifikasi pembayaran terkirim via Baileys ke', pelanggan.nomor_wa);
                    }
                } catch (baileysErr) {
                    console.log('Baileys tidak tersedia, coba Meta API...');
                }

                // Fallback: kirim via Meta API
                if (!sent) {
                    await kirimBalasanMeta(pelanggan.nomor_wa, pesan);
                }
            }

            console.log('Pembayaran berhasil untuk order:', order_id);
        }

        res.json({ status: 'OK' });
    } catch (error) {
        console.error('Gagal memproses webhook Midtrans:', error.message);
        res.status(500).json({ error: 'Internal server error' });
    }
});

// ============================================================
// ENDPOINT 3: API UNTUK ADMIN DASHBOARD
// ============================================================

// Ambil notifikasi admin (belum dibaca)
app.get('/api/notifikasi', async (req, res) => {
    try {
        const notifikasi = await query(
            'SELECT n.id, n.tipe, n.judul, n.isi, n.pelanggan_id, n.dibaca, n.created_at, p.nomor_wa, p.nama as nama_pelanggan FROM notifikasi_admins n LEFT JOIN pelanggan p ON n.pelanggan_id = p.id WHERE n.dibaca = 0 ORDER BY n.created_at DESC LIMIT 50'
        );
        res.json(notifikasi);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Tandai notifikasi sudah dibaca
app.post('/api/notifikasi/:id/read', async (req, res) => {
    try {
        await update('UPDATE notifikasi_admins SET dibaca = 1, updated_at = NOW() WHERE id = ?', [req.params.id]);
        res.json({ status: 'OK' });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Ambil semua notifikasi (termasuk sudah dibaca)
app.get('/api/notifikasi/all', async (req, res) => {
    try {
        const notifikasi = await query(
            'SELECT n.id, n.tipe, n.judul, n.isi, n.pelanggan_id, n.dibaca, n.created_at, p.nomor_wa, p.nama as nama_pelanggan FROM notifikasi_admins n LEFT JOIN pelanggan p ON n.pelanggan_id = p.id ORDER BY n.created_at DESC LIMIT 100'
        );
        res.json(notifikasi);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Ambil statistik chatbot
app.get('/api/stats', async (req, res) => {
    try {
        const totalPelanggan = await queryOne('SELECT COUNT(*) as total FROM pelanggan');
        const pelangganAktif = await queryOne('SELECT COUNT(*) as total FROM pelanggan WHERE sesi_aktif != ?', ['menu']);
        const percakapanHariIni = await queryOne('SELECT COUNT(*) as total FROM percakapan WHERE DATE(created_at) = CURDATE()');
        const notifikasiBelum = await queryOne('SELECT COUNT(*) as total FROM notifikasi_admins WHERE dibaca = 0');
        const transaksiPending = await queryOne('SELECT COUNT(*) as total FROM transaksi_chatbot WHERE status = ?', ['pending']);
        const transaksiPaid = await queryOne('SELECT COUNT(*) as total FROM transaksi_chatbot WHERE status = ?', ['paid']);

        res.json({
            total_pelanggan: totalPelanggan?.total || 0,
            pelanggan_aktif: pelangganAktif?.total || 0,
            percakapan_hari_ini: percakapanHariIni?.total || 0,
            notifikasi_belum: notifikasiBelum?.total || 0,
            transaksi_pending: transaksiPending?.total || 0,
            transaksi_paid: transaksiPaid?.total || 0,
        });
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// ============================================================
// ENDPOINT 4: API CHAT UNTUK ADMIN DASHBOARD (Bot ↔ Human)
// ============================================================

// Ambil daftar pelanggan dengan status sesi
app.get('/api/chat/pelanggan', async (req, res) => {
    try {
        const pelanggan = await query(
            `SELECT p.id, p.nomor_wa, p.nama, p.sesi_aktif, p.pesan_terakhir,
                (SELECT COUNT(*) FROM percakapan WHERE pelanggan_id = p.id) as total_chat,
                (SELECT created_at FROM percakapan WHERE pelanggan_id = p.id ORDER BY created_at DESC LIMIT 1) as chat_terakhir
            FROM pelanggan p
            ORDER BY p.pesan_terakhir DESC`
        );
        res.json(pelanggan);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Ambil riwayat percakapan pelanggan
app.get('/api/chat/messages/:pelangganId', async (req, res) => {
    try {
        const { pelangganId } = req.params;
        const { after_id } = req.query; // Untuk polling pesan baru

        let sql = 'SELECT id, pelanggan_id, pesan_pengirim, pesan_balasan, sumber_balasan, terkirim, created_at FROM percakapan WHERE pelanggan_id = ?';
        const params = [pelangganId];

        if (after_id) {
            sql += ' AND id > ?';
            params.push(after_id);
        }

        sql += ' ORDER BY created_at ASC LIMIT 100';

        const messages = await query(sql, params);
        res.json(messages);
    } catch (error) {
        res.status(500).json({ error: error.message });
    }
});

// Admin kirim pesan ke pelanggan — gunakan Baileys (indexB.js di port 3001)
app.post('/api/chat/send', async (req, res) => {
    try {
        const { pelanggan_id, pesan } = req.body;

        if (!pelanggan_id || !pesan) {
            return res.status(400).json({ error: 'pelanggan_id dan pesan wajib diisi' });
        }

        const pelanggan = await queryOne('SELECT id, nomor_wa, nama, sesi_aktif FROM pelanggan WHERE id = ?', [pelanggan_id]);
        if (!pelanggan) {
            return res.status(404).json({ error: 'Pelanggan tidak ditemukan' });
        }

        let sent = false;

        // Coba kirim via Baileys (indexB.js di port 3001)
        try {
            const response = await axios.post('http://127.0.0.1:3001/send', {
                jid: pelanggan.nomor_wa,
                pesan: pesan,
            }, { timeout: 5000 });

            if (response.data && response.data.status === 'OK') {
                sent = true;
            }
        } catch (baileysErr) {
            console.log('Baileys tidak tersedia, coba Meta API...');
        }

        // Fallback: kirim via Meta API (hanya untuk @s.whatsapp.net)
        if (!sent && pelanggan.nomor_wa.endsWith('@s.whatsapp.net')) {
            try {
                await axios.post(
                    `https://graph.facebook.com/v18.0/${ID_NOMOR_TELEPON}/messages`,
                    {
                        messaging_product: 'whatsapp',
                        to: pelanggan.nomor_wa.replace('@s.whatsapp.net', ''),
                        text: { body: pesan },
                    },
                    {
                        headers: {
                            'Authorization': `Bearer ${TOKEN_META}`,
                            'Content-Type': 'application/json',
                        },
                    }
                );
                sent = true;
            } catch (metaErr) {
                console.error('Gagal kirim via Meta API:', metaErr.response?.data || metaErr.message);
            }
        }

        // Simpan ke database
        const insertId = await insert(
            'INSERT INTO percakapan (pelanggan_id, pesan_pengirim, pesan_balasan, sumber_balasan, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
            [pelanggan_id, null, pesan, 'admin']
        );

        await update('UPDATE pelanggan SET pesan_terakhir = NOW() WHERE id = ?', [pelanggan_id]);

        res.json({
            status: 'OK',
            message_id: insertId,
            sent: sent,
            note: sent ? 'Pesan terkirim via WhatsApp' : 'Pesan disimpan (WhatsApp offline)',
        });
    } catch (error) {
        console.error('Gagal kirim pesan:', error.message);
        res.status(500).json({ error: 'Gagal mengirim pesan' });
    }
});

// Toggle mode bot/human untuk pelanggan
app.post('/api/chat/toggle', async (req, res) => {
    try {
        const { pelanggan_id, mode } = req.body; // mode: 'ai' atau 'human'

        if (!pelanggan_id || !['ai', 'human'].includes(mode)) {
            return res.status(400).json({ error: 'pelanggan_id dan mode (ai/human) wajib diisi' });
        }

        const pelanggan = await queryOne('SELECT id, nomor_wa, nama, sesi_aktif FROM pelanggan WHERE id = ?', [pelanggan_id]);
        if (!pelanggan) {
            return res.status(404).json({ error: 'Pelanggan tidak ditemukan' });
        }

        await update('UPDATE pelanggan SET sesi_aktif = ?, updated_at = NOW() WHERE id = ?', [mode, pelanggan_id]);

        // Kirim notifikasi ke pelanggan jika switch ke human
        if (mode === 'human') {
            const pesanNotif = 'Kak, saat ini admin kami sedang mengambil alih percakapan ini. Admin akan merespons secepatnya ya! 😊';
            await axios.post(
                `https://graph.facebook.com/v18.0/${ID_NOMOR_TELEPON}/messages`,
                {
                    messaging_product: 'whatsapp',
                    to: pelanggan.nomor_wa,
                    text: { body: pesanNotif },
                },
                {
                    headers: {
                        'Authorization': `Bearer ${TOKEN_META}`,
                        'Content-Type': 'application/json',
                    },
                }
            );
            await insert(
                'INSERT INTO percakapan (pelanggan_id, pesan_pengirim, pesan_balasan, sumber_balasan, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                [pelanggan_id, null, pesanNotif, 'system']
            );
        }

        // Kirim notifikasi ke pelanggan jika switch balik ke AI
        if (mode === 'ai') {
            const pesanNotif = 'Kak, percakapan ini sudah dikembalikan ke mode AI. Silakan tanyakan seputar parrot & 4PUTRA ya! 🦜';
            await axios.post(
                `https://graph.facebook.com/v18.0/${ID_NOMOR_TELEPON}/messages`,
                {
                    messaging_product: 'whatsapp',
                    to: pelanggan.nomor_wa,
                    text: { body: pesanNotif },
                },
                {
                    headers: {
                        'Authorization': `Bearer ${TOKEN_META}`,
                        'Content-Type': 'application/json',
                    },
                }
            );
            await insert(
                'INSERT INTO percakapan (pelanggan_id, pesan_pengirim, pesan_balasan, sumber_balasan, created_at, updated_at) VALUES (?, ?, ?, ?, NOW(), NOW())',
                [pelanggan_id, null, pesanNotif, 'system']
            );
        }

        res.json({ status: 'OK', new_mode: mode });
    } catch (error) {
        console.error('Gagal toggle mode:', error.message);
        res.status(500).json({ error: error.message });
    }
});

// ============================================================
// JALANKAN SERVER
// ============================================================
const PORT = 3000;
app.listen(PORT, () => {
    console.log('==================================================');
    console.log(`SERVER WEBHOOK 4PUTRA BERJALAN DI PORT ${PORT}`);
    console.log(`Meta Webhook: http://localhost:${PORT}/webhook`);
    console.log(`Midtrans Callback: http://localhost:${PORT}/midtrans/callback`);
    console.log(`Admin API: http://localhost:${PORT}/api/notifikasi`);
    console.log('==================================================');
});
