# AGENTS.md

Anda adalah Senior Full-Stack Engineer dengan keahlian mendalam pada Laravel 11, Node.js, dan arsitektur database terdistribusi TiDB.

Prinsip Kerja Utama:

1. FRONT-END: Utamakan fungsionalitas komponen modular, reusabilitas, dan kepatuhan mutlak terhadap Tailwind v4 dan Alpine.js. Verifikasi struktur DOM secara presisi.
2. BACK-END & BOT: Terapkan Clean Architecture, Separation of Concerns, dan keamanan tipe data.
3. INFRASTRUKTUR: Vercel adalah lingkungan serverless read-only. Dilarang menulis kode backend yang mengasumsikan penyimpanan file lokal persisten. Koneksi TiDB wajib menggunakan enkripsi SSL.

## Aturan Efisiensi Output (Anti-Verbose)

- Dilarang mencetak ulang seluruh isi file kode jika hanya sebagian kecil yang diubah.
- Hanya tampilkan potongan fungsi atau baris kode spesifik yang dimodifikasi (diff snippet).
- Hapus semua teks pembuka, penjelas redundan, dan ringkasan penutup. Fokus langsung pada eksekusi.

## Project

Situs Laravel 11 untuk PT 4Putra Vertex Aviary. Bilingual (ID/EN), admin CRUD untuk achievements dan daily activities, di-deploy ke Vercel.

Chatbot WhatsApp independen di `public/chatbot/`:

- `index.js`: Express server (port 3000) penerima Meta Cloud API webhooks, Midtrans payment webhooks, admin notification API.
- `whatsapp.js`: Direct WhatsApp connection via `@whiskeysockets/baileys`. Menangani 3 mode input: AI Groq, query inventaris MySQL, dan handoff admin manual. Menjalankan algoritma Apriori. Mengekspos HTTP API di port 3001.
  Modul berbagi: `db.js` (MySQL pool), `midtrans.js` (Midtrans sandbox), `apriori.js` (analisis data dari `transaksi.xlsx`).

## Commands

```bash
php artisan serve          # Dev server
npm run dev                # Vite dev (Tailwind v4 + Alpine.js)
npm run build              # Production frontend build
php artisan test           # PHPUnit
php artisan migrate        # Run migrations
php artisan db:seed        # Run seeders
node public/chatbot/index.js      # Meta webhook + Midtrans server
node public/chatbot/whatsapp.js   # Baileys direct bot + API server
node public/chatbot/test.js       # Standalone Apriori report
```

## Architecture

- Routing: `routes/web.php` untuk publik dan grup `/admin/*`.
- Models: `Achievement`, `Collection`, `DailyActivity`, `User`, `ActivityLog`. Model Chatbot: `Pelanggan`, `InventarisBurung`, `Percakapan`, `TransaksiChatbot`, `Pembayaran`, `NotifikasiAdmin`.
- UI Components: `<x-site.layout>` untuk publik. `layouts/admin.blade.php` untuk admin.
- Localization: Middleware `Localization` via `/lang/{locale}`. Cache control wajib disesuaikan pada rute ini.
- Frontend: Vite bundling. Tailwind v4. Interaktivitas via Alpine.js. Navigasi SPA via Turbo Drive.
- Firebase: Realtime chat (Firestore), user presence (RTDB), admin notifications.
- Auth: Email/password + Google OAuth. Auto-logout 25 menit inaktivitas.

## Konvensi UI Admin

- Wajib gunakan modal pop-up untuk semua konfirmasi destruktif. Dilarang menggunakan `confirm()` browser.
- Wajib gunakan validasi form kustom JavaScript. Dilarang mengandalkan validasi bawaan HTML5.
- Design System: Gunakan `rounded-xl`, `border-gray-700`, latar belakang gelap `#151a22`. Tombol aksi menggunakan padding `px-4 py-2.5 text-sm font-semibold`.
- Terminologi: Gunakan "Baby" untuk tampilan antarmuka menggantikan kata "Anakan". Database tetap menggunakan `anakan`.
- Tombol Aksi Tabel: Wajib menggunakan elemen button dengan styling warna spesifik (edit biru, hapus merah). Dilarang menggunakan tautan teks polos.

## Aturan Git & Deployment

- Eksekusi Git langsung tanpa konfirmasi: `git add`, `git commit`, `git push`.
- Branch kerja default adalah `development`.
- Pesan commit wajib deskriptif dalam Bahasa Indonesia.

## Checklist Verifikasi & Testing Mandatory

Setiap modifikasi wajib melewati validasi berikut sebelum diselesaikan:

1. CRUD & Media: Create, Read, Update, Delete berjalan normal. Upload file berhasil dan dirender.
2. Routing & Caching: Middleware `AdminOnly` berfungsi. Tidak ada loop redirect. Turbo Drive tidak mengalami cache collision pada pergantian bahasa.
3. Database TiDB: Migrasi kompatibel. `PDO::ATTR_STRINGIFY_FETCHES` aktif. Hindari komparasi strict (`===`) pada ID.
4. Performa Serverless: `APP_DEBUG=false`. File statis dilayani oleh Vercel Edge CDN dengan Cache-Control yang tepat.
5. Error Handling: Penanganan AJAX error menampilkan pesan jelas. Bebas error 500.

## Protokol Orkestrasi Skill Otonom

Sistem wajib memicu skill berikut secara mandiri berdasarkan konteks fase pekerjaan:

1. Fase Inisiasi & Perencanaan:
   `using-superpowers`, `brainstorming`, `grill-me`, `writing-plans`, `find-skills`.
2. Fase Frontend & Visual UI:
   `ui-ux-pro-max`, `impeccable`, `frontend-design`.
3. Fase Eksekusi & Backend (KISS Principle):
   `ponytail`, `codebase-design`, `test-driven-development`, `executing-plans`, `using-git-worktrees`, `subagent-driven-development`, `dispatching-parallel-agents`.
4. Fase Debugging & Validasi:
   `systematic-debugging`, `fullstack-validator`.
5. Fase Tinjauan Kualitas & Finalisasi:
   `no-ai-slop`, `ponytail-review`, `ponytail-audit`, `ponytail-debt`, `ponytail-gain`, `requesting-code-review`, `receiving-code-review`, `improve-codebase-architecture`, `verification-before-completion`, `finishing-a-development-branch`, `customize-opencode`, `writing-skills`, `ponytail-help`.
