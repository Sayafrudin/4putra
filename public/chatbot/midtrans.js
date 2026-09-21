import axios from 'axios';
import crypto from 'crypto';

// Konfigurasi Midtrans dibaca LAZY (per-call) — const module-level menangkap
// process.env SEBELUM config() dotenv di whatsapp.js/index.js berjalan (ESM
// evaluasi import dulu), sehingga key selalu undefined → 401 Midtrans.
function dapatkanServerKey() {
    return process.env.MIDTRANS_SERVER_KEY || '';
}
function dapatkanBaseUrl() {
    return process.env.MIDTRANS_BASE_URL || 'https://app.sandbox.midtrans.com';
}
// Core API memakai host api.* (bukan app.* milik Snap)
function dapatkanApiBaseUrl() {
    return dapatkanBaseUrl().includes('sandbox') ? 'https://api.sandbox.midtrans.com' : 'https://api.midtrans.com';
}

// Membuat transaksi Snap dan mengembalikan token + redirect URL
export async function createTransaction(orderId, grossAmount, customerDetails = {}, itemDetails = []) {
    const auth = Buffer.from(dapatkanServerKey() + ':').toString('base64');

    const payload = {
        transaction_details: {
            order_id: orderId,
            gross_amount: Math.round(grossAmount),
        },
        customer_details: {
            first_name: customerDetails.nama || 'Pelanggan',
            phone: customerDetails.nomor || '',
        },
        item_details: itemDetails.length > 0 ? itemDetails : [
            {
                id: orderId,
                price: Math.round(grossAmount),
                quantity: 1,
                name: customerDetails.nama_produk || 'Produk 4Putra',
            },
        ],
        // Tanpa enabled_payments: Snap menampilkan semua channel aktif pada
        // pengaturan akun Midtrans — filter ['qris'] membuat halaman kosong
        // ("No payment channels available") bila QRIS tidak diaktifkan di dashboard.
        credit_card: {
            secure: true,
        },
    };

    try {
        const response = await axios.post(`${dapatkanBaseUrl()}/snap/v1/transactions`, payload, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Basic ${auth}`,
            },
        });

        return {
            token: response.data.token,
            redirect_url: response.data.redirect_url,
        };
    } catch (error) {
        console.error('Gagal membuat transaksi Midtrans:', error.response?.data || error.message);
        throw error;
    }
}

// Membuat charge QRIS via Core API (payment_type: "qris") — menghasilkan URL gambar QR
export async function createQrisCharge(orderId, grossAmount, customerDetails = {}, itemDetails = []) {
    const auth = Buffer.from(dapatkanServerKey() + ':').toString('base64');

    const payload = {
        payment_type: 'qris',
        transaction_details: {
            order_id: orderId,
            gross_amount: Math.round(grossAmount),
        },
        customer_details: {
            first_name: customerDetails.nama || 'Pelanggan',
            phone: customerDetails.nomor || '',
        },
        item_details: itemDetails.length > 0 ? itemDetails : [
            {
                id: orderId,
                price: Math.round(grossAmount),
                quantity: 1,
                name: customerDetails.nama_produk || 'Produk 4Putra',
            },
        ],
        qris: {
            acquirer: 'gopay',
        },
    };

    try {
        const response = await axios.post(`${dapatkanApiBaseUrl()}/v2/charge`, payload, {
            headers: {
                'Content-Type': 'application/json',
                'Authorization': `Basic ${auth}`,
            },
        });

        const qrAction = (response.data.actions || []).find(a => a.name === 'generate-qr-code');

        return {
            token: response.data.token,
            status_code: response.data.status_code,
            qrImageUrl: qrAction?.url || null,
            redirect_url: response.data.redirect_url || null,
        };
    } catch (error) {
        console.error('Gagal membuat charge QRIS Midtrans:', error.response?.data || error.message);
        throw error;
    }
}

// Download gambar QR (butuh Basic auth)
export async function unduhGambarQr(url) {
    const auth = Buffer.from(dapatkanServerKey() + ':').toString('base64');
    const response = await axios.get(url, {
        headers: { 'Authorization': `Basic ${auth}` },
        responseType: 'arraybuffer',
        timeout: 15000,
    });
    return Buffer.from(response.data);
}

// Mendapatkan status transaksi (Core API di host api.* — host app.* milik Snap saja)
export async function getTransactionStatus(orderId) {
    const auth = Buffer.from(dapatkanServerKey() + ':').toString('base64');

    try {
        const response = await axios.get(`${dapatkanApiBaseUrl()}/v2/${orderId}/status`, {
            headers: {
                'Authorization': `Basic ${auth}`,
            },
        });

        return response.data;
    } catch (error) {
        console.error('Gagal mendapatkan status transaksi:', error.response?.data || error.message);
        throw error;
    }
}

// Verifikasi signature dari webhook Midtrans
export function verifySignature(orderId, statusCode, grossAmount, signatureKey) {
    const input = orderId + statusCode + grossAmount + dapatkanServerKey();
    const computed = crypto.createHash('sha512').update(input).digest('hex');
    return computed === signatureKey;
}

// Konfigurasi (getter agar tetap membaca env terkini)
export const config = {
    get isProduction() { return !dapatkanBaseUrl().includes('sandbox'); },
    get serverKey() { return dapatkanServerKey(); },
    get clientKey() { return process.env.MIDTRANS_CLIENT_KEY; },
    get baseUrl() { return dapatkanBaseUrl(); },
};
