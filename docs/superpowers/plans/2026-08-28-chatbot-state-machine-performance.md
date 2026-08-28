# Chatbot State Machine, Performa Backend & UI Admin — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans (inline) untuk implementasi task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Menuntaskan delta performa (blur/caching/N+1/PDO), restyle widget chat admin gelap solid, repo hygiene chatbot, dan rekonstruksi state machine WhatsApp (metadata_sesi, AI sticky, Snap QRIS).

**Architecture:** Laravel 11 (Vercel serverless + TiDB) + Node bot Baileys (`public/chatbot/whatsapp.js`) dengan state machine berbasis kolom `pelanggan.sesi_aktif` + konteks sementara JSON di kolom baru `metadata_sesi`.

**Tech Stack:** Laravel 11, TiDB (PDO stringify), Tailwind v4, Vite, Firebase (Firestore + RTDB), Baileys v7, Groq SDK, Midtrans Snap API.

**Spec:** instruksi pengguna 2026-08-28 + keputusan: Snap API (`enabled_payments: ['qris']`), state names `inventory`/`checkout` dipertahankan, Order ID `4PUTRA-{timestamp}-{pelangganId}`.

## Global Constraints

- Vercel read-only: dilarang asumsi penyimpanan file lokal persisten untuk request baru.
- TiDB: SSL wajib, `PDO::ATTR_STRINGIFY_FETCHES` aktif, hindari komparasi strict `===` pada ID.
- Terminologi UI: "Baby" (bukan "Anakan"); DB tetap `anakan`.
- Commit deskriptif Bahasa Indonesia; branch kerja `development`; eksekusi git langsung tanpa konfirmasi.
- Verifikasi mandatory: `npm run build`, `php artisan test`, `php artisan migrate`.

---

### Task 1: Hapus blur render klien

**Files:**
- Modify: `resources/js/script.js:24`

- [x] Ganti `bg-black/20 backdrop-blur-md` → `bg-black/85` (solid tanpa filter blur). `drop-shadow-xl` di hero image dibiarkan (bayangan, bukan blur).
- [x] `npm run build` sukses.

### Task 2: Cache gap, PDO persisten, test

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminCollectionController.php` (destroy)
- Modify: `config/database.php` (options non-TiDB)
- Test: `tests/Feature/CollectionCacheTest.php` (baru)

- [x] `Cache::forget('public.collections')` di `AdminCollectionController::destroy`.
- [x] `PDO::ATTR_PERSISTENT => true` ditambahkan ke array options branch non-TiDB.
- [x] Feature test invalidasi cache koleksi; `php artisan test` hijau.

### Task 3: Widget chat admin gelap solid

**Files:**
- Modify: `resources/js/chat.js` (renderWidget + bubble/preview styles)

- [x] Panel `bg-white` → `bg-[#151a22]`, border `border-gray-700`, `rounded-xl`, teks terang; tanpa blur; bubble tema gelap.
- [x] `npm run build` sukses.

### Task 4: Repo hygiene chatbot

**Files:**
- Modify: `public/chatbot/.gitignore`
- Untrack: `public/chatbot/package.json`, `package-lock.json`, `.env.example`

- [x] `.gitignore` = `node_modules/`, `package-lock.json`, `package.json`, `.env.example`, `.env`, `auth_info/`, `*.log`.
- [x] `git rm --cached` tiga file tersebut.

### Task 5: Kolom `metadata_sesi`

**Files:**
- Create: `database/migrations/2026_08_28_000002_add_metadata_sesi_to_pelanggan_table.php`
- Modify: `app/Models/Pelanggan.php`

- [x] Migration `ALTER TABLE pelanggan ADD COLUMN metadata_sesi JSON NULL AFTER riwayat_konteks` (down: drop).
- [x] Model: fillable + cast `metadata_sesi` => `array`.
- [x] `php artisan migrate` sukses.

### Task 6: State machine `whatsapp.js`

**Files:**
- Modify: `public/chatbot/whatsapp.js`

- [x] `dapatkanPelanggan`: SELECT + object baru memuat `metadata_sesi`.
- [x] Idle reset 6 jam: hanya `inventory`, `checkout` (AI tidak pernah reset otomatis).
- [x] Gate deteksi burung/Apriori: hanya saat `sesi_aktif === 'menu'`.
- [x] Swap konteks `riwayat_konteks` → `metadata_sesi` (items `{id,nama,fase,harga}`; target item; `menu`/post-QRIS → NULL).
- [x] Pesan qty persis: `Kakak pilih *[Nama]* ([Fase]) — Rp [Harga]/ekor. Mau beli berapa ekor? Ketik jumlahnya ya, Kak (contoh: 1, 2, 3)`.
- [x] Mode AI: exit hanya `teksMasukLower === 'menu'`; Groq try/catch; kirim `await` langsung (hapus `setTimeout` tanpa await) di jalur AI & rekomendasi.
- [x] `buatPembayaranQRIS`: Midtrans **Snap** `createTransaction` + `enabled_payments:['qris']`; simpan pending; `qr_url` = `redirect_url`; kembali ke `menu` + `metadata_sesi=NULL`; kirim template sukses persis (✅/📦/💳/⏰ + `[Link_Midtrans]`).

### Task 7: Verifikasi

- [x] `node --check public/chatbot/whatsapp.js`
- [x] `npm run build`
- [x] `php artisan test`
- [x] Tracing state machine (statik): menu→2/5→pilih→qty→Snap→menu; AI sticky; label forwarded kondisional.

### Task 8: Pipeline Git

- [x] Commit di `development` (pesan sesuai instruksi user), push, PR `development` → `main`, `gh pr merge --merge`, tetap di `development`.
