import { config } from 'dotenv';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const __dirname = dirname(fileURLToPath(import.meta.url));
config({ path: join(__dirname, '../../.env') });
config({ path: join(__dirname, '.env'), override: true });

import { initializeApp, cert, getApps } from 'firebase-admin/app';
import { getDatabase, ref, set, remove, serverTimestamp } from 'firebase-admin/database';

let rtdb = null;

function getRtdb() {
    if (!rtdb) {
        const projectId = process.env.FIREBASE_PROJECT_ID;
        const clientEmail = process.env.FIREBASE_CLIENT_EMAIL;
        const privateKey = (process.env.FIREBASE_PRIVATE_KEY || '').replace(/\\n/g, '\n');

        if (!projectId || !clientEmail || !privateKey) {
            throw new Error('FIREBASE_PROJECT_ID / FIREBASE_CLIENT_EMAIL / FIREBASE_PRIVATE_KEY belum diisi di .env');
        }

        const existing = getApps().find(a => a.name === 'chatbot-admin');
        const app = existing || initializeApp({
            credential: cert({ projectId, clientEmail, privateKey }),
            databaseURL: process.env.FIREBASE_DATABASE_URL || 'https://putra-project-502403-default-rtdb.asia-southeast1.firebasedatabase.app',
        }, 'chatbot-admin');

        rtdb = getDatabase(app);
    }
    return rtdb;
}

// Tulis sinyal handoff realtime untuk Admin Dashboard
export async function kirimHandoffKeFirebase(pelangganId, { nama, nomor }) {
    try {
        await set(ref(getRtdb(), `chatbot_handoffs/${pelangganId}`), {
            nama: nama || null,
            nomor: nomor || null,
            sesi: 'human',
            timestamp: serverTimestamp(),
        });
        return true;
    } catch (e) {
        console.error('[FIREBASE] Gagal kirim handoff:', e.message);
        return false;
    }
}

// Hapus sinyal handoff (bot kembali mengontrol)
export async function hapusHandoffFirebase(pelangganId) {
    try {
        await remove(ref(getRtdb(), `chatbot_handoffs/${pelangganId}`));
        return true;
    } catch (e) {
        console.error('[FIREBASE] Gagal hapus handoff:', e.message);
        return false;
    }
}
