import axios from 'axios';
import crypto from 'crypto';

// Konfigurasi Midtrans dari environment variables
const MIDTRANS_SERVER_KEY = process.env.MIDTRANS_SERVER_KEY;
const MIDTRANS_CLIENT_KEY = process.env.MIDTRANS_CLIENT_KEY;
const BASE_URL = process.env.MIDTRANS_BASE_URL || 'https://app.sandbox.midtrans.com';

// Membuat transaksi Snap dan mengembalikan token + redirect URL
export async function createTransaction(orderId, grossAmount, customerDetails = {}, itemDetails = []) {
    const auth = Buffer.from(MIDTRANS_SERVER_KEY + ':').toString('base64');

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
        credit_card: {
            secure: true,
        },
    };

    try {
        const response = await axios.post(`${BASE_URL}/snap/v1/transactions`, payload, {
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

// Mendapatkan status transaksi
export async function getTransactionStatus(orderId) {
    const auth = Buffer.from(MIDTRANS_SERVER_KEY + ':').toString('base64');

    try {
        const response = await axios.get(`${BASE_URL}/v2/${orderId}/status`, {
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
    const input = orderId + statusCode + grossAmount + MIDTRANS_SERVER_KEY;
    const computed = crypto.createHash('sha512').update(input).digest('hex');
    return computed === signatureKey;
}

// Konfigurasi
export const config = {
    isProduction: false,
    serverKey: MIDTRANS_SERVER_KEY,
    clientKey: MIDTRANS_CLIENT_KEY,
    baseUrl: BASE_URL,
};
