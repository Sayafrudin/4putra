# AGENTS.md

## Project

Laravel 11 site for PT 4Putra Vertex Aviary (parrot breeding business). Bilingual (Indonesian/English), admin CRUD for achievements, deployed to Vercel.

WhatsApp chatbot for PT 4Putra Vertex Aviary (bird shop in Surabaya). Two independent bot entry points in `public/chatbot/`:

- **`index.js`** — Express server (port 3000) receiving Meta Cloud API webhooks + Midtrans payment webhooks + admin notification API.
- **`indexB.js`** → renamed to `whatsapp.js` — Direct WhatsApp connection via `@whiskeysockets/baileys`. Handles 3 input modes: [1] Groq AI consultation, [2] inventory queries from MySQL, [3] manual handoff to admin. Also runs Apriori recommendations on bird keywords. Exposes HTTP API on port 3001 for sending messages from admin dashboard.

Both share `db.js` (MySQL connection pool), `midtrans.js` (Midtrans sandbox client), and `apriori.js` (data mining on `transaksi.xlsx`).

## Commands

```bash
php artisan serve          # dev server
npm run dev                # Vite dev (Tailwind v4 + Alpine.js)
npm run build              # production frontend build
php artisan test           # PHPUnit (Unit + Feature suites)
php artisan migrate        # run migrations
php artisan db:seed        # run seeders
./vendor/bin/pint          # Laravel Pint formatter (no config file, uses defaults)
```

**Chatbot commands** (run from repo root):

```bash
node public/chatbot/index.js      # Meta webhook + Midtrans callback server (port 3000)
node public/chatbot/whatsapp.js   # Baileys direct bot + API server (port 3001, needs QR scan on first run)
node public/chatbot/test.js       # Standalone Apriori analysis report
```

## Architecture

- **Routing**: `routes/web.php` — public pages (/, /collections, /achievements, /about, /contact) + admin group at `/admin/*`
- **Models**: `Achievement` hasMany `AchievementImage`. `Collection` for bird catalog. `User` has `role` (admin/user), `google_id`, `last_login_at`, `last_active_at`. `ActivityLog` for audit trail. Chatbot models: `Pelanggan`, `InventarisBurung`, `Percakapan`, `TransaksiChatbot`, `Pembayaran`, `NotifikasiAdmin`.
- **Admin controllers**: `app/Http/Controllers/Admin/` — `DashboardController`, `AdminAchievementController`, `AdminCollectionController`, `AdminUserController`, `ProfileController`, `ChatController`, `ChatbotController`
- **Public controller**: `app/Http/Controllers/AchievementController`, `CollectionController`
- **Views**: Public pages use `<x-site.layout>` component. Admin uses `layouts/admin.blade.php` with `@yield('content')`.
- **Components**: Public site uses `components/site/*` (card, divider, footer, layout, navbar, skeleton, whatsapp). Admin uses `components/admin/*` (card, modal, skeleton, sidebar, toast, chat-widget, etc.).
- **Localization**: Session-based via custom `Localization` middleware registered in `bootstrap/app.php`. Switch route: `/lang/{locale}` (supports `en`, `id`). Translation files in `lang/en.json` and `lang/id.json`.
- **Frontend**: Vite bundles `resources/css/app.css`, `resources/js/app.js`, `resources/js/chat.js`. JS imports Alpine.js, Dropzone (file upload in admin). Tailwind v4 via `@tailwindcss/vite` plugin. Code-split: firebase, alpine, chat are separate chunks.
- **Firebase**: Used for realtime chat (Firestore), user presence (RTDB), and admin notifications. Config in `resources/js/chat.js`. Presence written at `presence/{userId}` in RTDB.
- **Auth**: Email/password login + Google OAuth via `laravel/socialite`. Session timeout auto-logout after 15 min inactivity (`public/js/session-timeout.js`).
- **Middleware**: `TrackActivity` updates `last_active_at` on every request. `AdminAuth` checks auth. `AdminOnly` checks admin role.

## Chatbot Architecture

```
public/chatbot/
  db.js         → MySQL connection pool (mysql2/promise)
  midtrans.js   → Midtrans Snap API client (sandbox mode)
  index.js      → Express server (port 3000): Meta webhook + Midtrans callback + admin notification API
  whatsapp.js   → Baileys socket: 3-mode routing (AI / inventory / manual) + Apriori recommendations
  apriori.js    → reads transaksi.xlsx → runs Apriori algorithm → returns strong association rules
  test.js       → standalone script to print Apriori analysis results to console
  transaksi.xlsx → historical transaction data for Apriori analysis
```

**Alur Pesan `whatsapp.js`:**

1. Pesan masuk → cek/insert `pelanggan` table (by nomor WhatsApp)
2. Cek rate limit (jika < 2 detik dari pesan terakhir → skip)
3. Cek `sesi_aktif` dari database:
    - `'menu'` → tampilkan menu: [1] Konsultasi AI [2] Inventaris [3] Hubungi Admin [4] Riwayat Transaksi
    - `'ai'` → ambil 10 percakapan terakhir → kirim ke Groq AI dengan system prompt + history
    - `'manual'` → simpan pesan, kirim notifikasi real-time ke admin via Firebase
    - `'human'` → admin sedang mengambil alih, bot diam, simpan pesan saja
4. Jika mode `'menu'` dan input angka 1/2/3/4 → ubah sesi dan proses sesuai mode
5. Jika input mengandung keyword burung (5 spesies) → jalankan Apriori → kirim rekomendasi natural
6. Simpan semua pasangan (pesan, balasan) ke tabel `percakapan`

**System Prompt Restrictions:**

- Hanya merespons topik tentang: African Grey, BNG Macaw, Sun Conure, Monk Parakeet, Indian Ring Neck
- Fase anakan: pakan loloh, pengaturan suhu, perawatan khusus
- Fase dewasa: diet biji/pelet, perilaku mandiri, perawatan umum
- Jika topik di luar scope → belokkan ke inventaris 4PUTRA
- Jika ada niat beli/negosiasi/keluhan → sarankan ketik `3` untuk hubungi admin

**Alur Midtrans/QRIS:**

1. Admin buat transaksi → sistem kirim request ke Midtrans Sandbox
2. Midtrans generate QRIS → chatbot kirim gambar QRIS ke pelanggan
3. Pelanggan bayar → Midtrans webhook ke `POST /midtrans/callback`
4. Sistem verifikasi signature → update status → kirim notifikasi ke admin + tanda terima ke pelanggan

## Deployment

Vercel via `vercel.json` — PHP runtime for `api/index.php`, static assets served from `/public`. Cache and views written to `/tmp` in production. `public/build` is gitignored (commented out) — build artifacts may need to be committed for Vercel if not building in CI.

## Gotchas

- **DB for tests**: `phpunit.xml` has SQLite lines commented out. Tests use whatever `DB_CONNECTION` is in `.env`. For local testing, either uncomment the SQLite lines in `phpunit.xml` or set `DB_CONNECTION=sqlite` and `DB_DATABASE=:memory:` in `.env`.
- **Migrations exist**: `database/migrations/` has tables for users, cache, jobs, achievements, collections, activity_logs, plus Google auth and role columns. Run `php artisan migrate` before the app works.
- **Admin store/update return JSON**, not redirects. The admin Blade views use JS fetch/AJAX for form submissions, not standard form posts.
- **Images stored at** `storage/app/public/achievements/` and `storage/app/public/collections/`. Run `php artisan storage:link` for public access.
- **No Tailwind CDN** in admin layout — removed. Only Vite bundle is used.
- **No anime.js** — all animations use CSS transitions/Tailwind classes. Removed from dependencies.
- **Toast system**: `public/js/toast.js` exposes `window.showToast(type, title, message)`. Call from any page. Toast container is `<x-admin.toast>` component.
- **Skeleton components**: `components/admin/skeleton.blade.php` and `components/site/skeleton.blade.php` for loading states. Use `type` prop: table, card, stat, list (admin) or card, hero, achievement, team, marquee (site).

## Konvensi UI Admin

- **WAJIB gunakan modal pop-up** untuk semua aksi yang memerlukan konfirmasi: hapus, insert, edit, delete, atau aksi destruktif lainnya. **JANGAN** gunakan `confirm()` bawaan browser.
- **WAJIB gunakan custom form validation** (JavaScript) untuk semua form. **JANGAN** mengandalkan HTML5 `required` attribute atau browser default validation. Validasi harus menampilkan pesan error di bawah input field dengan styling yang konsisten (merah, teks kecil).
- **Design System Modal**: Gunakan class `fixed inset-0 z-50 hidden` dengan `bg-black/60 backdrop-blur-sm`. Box modal gunakan `rounded-2xl border border-gray-700`. Lihat contoh di `resources/views/admin/chatbot/inventaris/index.blade.php`.
- **Design System Form**: Input field gunakan `rounded-xl border-gray-700 bg-[#151a22]`. Button gunakan `rounded-xl`. Semua elemen harus konsisten menggunakan rounded corners.
- **Design System Buttons**: Tombol aksi utama gunakan `rounded-xl` dengan padding `px-4 py-2.5 text-sm font-semibold`. Tombol batal/secondary gunakan `bg-[#151a22] border border-gray-600 rounded-xl`.
- **Pattern form validation**: Buat fungsi `validateForm(prefix)` yang mengecek setiap field dan menampilkan error via `tampilkanError(fieldId, pesan)`. Error element harus `<p class="text-xs text-red-400 mt-1 hidden" id="err_[prefix]_[field]">`.
- **Konsistensi terminologi**: Gunakan "Baby" bukan "Anakan" di semua tampilan user-facing. Database tetap pakai `anakan` di enum, tapi tampilkan sebagai "Baby" di UI.
- **WAJIB gunakan Button untuk Aksi CRUD**: Di tabel admin, kolom aksi WAJIB menggunakan button dengan styling yang jelas (background color, border, rounded). **JANGAN** gunakan link teks biasa (`<a>` atau `<button>` tanpa styling). Gunakan pattern: `px-3 py-1.5 text-xs font-bold rounded-lg` dengan warna berbeda untuk setiap aksi (edit = blue, hapus = red, dll).
- **Prinsip desain**: Rounded corners, modern, ringan, konsisten. Hindari desain kotak/square, border teks, atau warna terlalu gelap untuk background modal.

## Key Facts

- **ES Modules** — `"type": "module"` in root `package.json`. All `.js` files use `import`/`export`, not `require()`.
- **No build/typecheck/lint** for chatbot — Run chatbot files directly with `node public/chatbot/index.js` or `node public/chatbot/whatsapp.js`.
- **No test suite** — `test.js` is a standalone Apriori analysis runner, not a test framework. Run it with `node public/chatbot/test.js`.
- **Runtime data** — `apriori.js` reads `transaksi.xlsx` via `import.meta.url` + `path.join(__dirname, ...)`, resolving relative to its own file location. Works from any cwd.
- **Session state** — `auth_info/` holds Baileys login credentials. Do not delete or commit this folder.
- **API keys are hardcoded** in `public/chatbot/index.js` and `public/chatbot/whatsapp.js`. Do not extract or rotate without confirming with the owner.
- **Language** — All user-facing strings and code comments are in Indonesian.
- **Database** — Chatbot uses the same MySQL database as Laravel app (`4putra-project` on `127.0.0.1:3306`). Node.js connects via `mysql2` package. Run `php artisan migrate` to create all tables.
- **Midtrans sandbox** — Payment integration uses Midtrans sandbox environment. Change `isProduction: false` to `true` in `midtrans.js` for production.

## Instruksi Bahasa & Komunikasi

- **Bahasa Utama**: Semua interaksi, penjelasan kode, ringkasan rencana, dan komunikasi dengan pengguna WAJIB menggunakan Bahasa Indonesia yang formal namun mudah dipahami.
- **Komentar Kode**: Tulis komentar di dalam file kode menggunakan Bahasa Indonesia.
- **Penamaan Variabel/Fungsi**: Tetap gunakan Bahasa Inggris standar industri untuk penamaan fungsi, variabel, dan kelas agar kode tetap bersih (clean code).

## Protokol Perubahan Kode

- **Wajib Klarifikasi**: Sebelum mengubah, mengganti, atau memodifikasi kode apa pun di dalam proyek, AI wajib bertanya dan meminta persetujuan pengguna terlebih dahulu.
- **Konfirmasi Rencana**: AI harus memaparkan rencana perubahan secara singkat sebelum mendapatkan izin eksekusi dari pengguna.

## Aturan Universal Modifikasi Kode, UI, & Validasi Fungsi

## Aturan Universal Modifikasi Kode, UI, & Validasi Fungsi

- **Isolasi dan Ketepatan Perubahan:** Saat diminta melakukan modifikasi, penambahan, atau refactoring pada aspek apa pun dalam proyek—baik visual UI (modal, toast, tabel, layout), logika program (controller, middleware, database routing), maupun seluruh integrasi sistem (API, database, library bawaan, serta paket pihak ketiga mana pun tanpa terkecuali)—perubahan harus dilakukan secara presisi dan tepat sasaran sesuai Design System dan arsitektur proyek[cite: 1]. Dilarang keras merusak, menghapus, atau menyederhanakan kode fungsi eksisting yang sudah berjalan sukses tanpa persetujuan tertulis dari pengguna[cite: 1].
- **Kemampuan Multimodal & Analisis Gambar Universal:** AI Open Code wajib memanfaatkan fitur pembacaan gambar (vision capabilities) pada model apa pun yang sedang aktif saat pengguna memberikan referensi visual (seperti tangkapan layar UI, mock-up desain, alur skema, diagram, atau foto error/bug). AI harus menganalisis elemen visual tersebut secara cermat untuk memastikan implementasi kode, tata letak UI, atau perbaikan bug berjalan presisi sesuai gambaran visual yang diberikan.
- **Efisiensi Token & Akurasi Tinggi:** AI wajib meminimalkan penggunaan token dan kredit dalam setiap interaksi, pembacaan, maupun penulisan kode. Berikan perubahan yang ringkas, hilangkan kode bawaan yang tidak perlu diubah, dan fokus hanya pada baris kode yang relevan agar hasil eksekusi lebih akurat, presisi, serta terhindar dari bias atau kesalahan akibat pemrosesan konteks yang terlalu panjang.
- **Standar Performa & Website Ringan:** Setiap pengembangan atau perubahan kode wajib menjaga situs tetap ringan, cepat dibuka, hemat data, dan berukuran kecil[cite: 1].
    - **Ukuran Halaman & Aset:** Usahakan total beban data teroptimasi dengan baik. Penggunaan gambar format JPG, PNG, maupun WebP diperbolehkan selama ukurannya terkompresi dan memiliki dimensi piksel yang pas. Penggunaan video juga diperbolehkan dengan kompresi yang optimal serta muatan (loading) yang tidak memberatkan halaman.
    - **Minimalisir Skrip & Plugin:** Gunakan dependensi, skrip luar, atau paket tambahan seperlunya saja[cite: 1].
    - **Desain Ringkas:** Jaga tata letak tetap bersih dan terstruktur secara efisien.
    - **Kode Bersih & Caching:** Tulis HTML, CSS, dan JavaScript secara efisien tanpa kode sisa yang tidak terpakai, serta manfaatkan sistem penyimpanan sementara (cache).
- **Wajib Uji Menyeluruh Pasca Perubahan (Regresi Prohibited):** Setiap kali ada baris kode yang diubah, ditambah, atau diperbaiki, sistem WAJIB diuji kembali secara mandiri sebelum menyerahkan hasil pekerjaan[cite: 1].
    - Perubahan UI harus diuji ketepatan penempatannya, kondisi kemunculannya, dan fungsionalitas fitur di baliknya[cite: 1].
    - Perubahan Logika/Fungsi harus diuji input-output data, penanganan error, dan integrasi end-to-end untuk memastikan tidak ada fitur lain yang ikut rusak (zero regression)[cite: 1].
    - Penambahan paket (package) baru atau implementasi hal-hal baru wajib diuji fungsionalitasnya secara penuh, kompatibilitasnya dengan sistem yang ada, serta memastikan tidak menimbulkan konflik atau mematahkan fitur lama.
- **Penanganan Gagal Uji:** Jika hasil pengujian mandiri pasca perubahan menunjukkan adanya kegagalan fungsi, anomali visual, atau error sistem, segera lakukan debugging mendalam pada baris kode terkait[cite: 1]. Perbaiki masalah tersebut hingga fungsi kembali berjalan 100% sukses dan normal sebelum melaporkan progress kepada pengguna[cite: 1].
- **Format Laporan Pengujian Kode & Komponen:** Setiap penyelesaian tugas modifikasi wajib disertai laporan hasil pengujian konkret di akhir respon dengan struktur sebagai berikut[cite: 1]:
    - **Aspek/Kode yang Diubah:** [Nama file, komponen, fungsi logika, implementasi baru, atau package baru yang dimodifikasi/ditambahkan]
    - **Status Fungsionalitas Sistem:** [Berjalan Normal / Tepat Sasaran / Mengalami Kendala][cite: 1]
    - **Bukti/Alur Tes:** [Penjelasan langkah demi langkah bagaimana perubahan, penambahan fitur, atau package baru tersebut diuji secara mandiri dan dibuktikan sukses tanpa merusak fitur lainnya]

## Aturan Git & Deployment

- **Branch `development` adalah branch default dan utama.** JANGAN PERNAH hapus branch `development`. Semua perubahan WAJIB push ke `development` dulu.
- **Alur push ke production:** Push ke `development` → buat PR ke `main` → langsung merge PR. Jangan biarkan PR menggantung tanpa di-merge.
- **JANGAN pernah push langsung ke `main`.** Selalu lewat `development` dulu, kecuali pengguna secara eksplisit meminta push ke `main`.
- **Push Langsung ke GitHub:** Ketika pengguna meminta push ke GitHub, lakukan langsung tanpa bertanya lagi. Jangan menunggu konfirmasi tambahan — langsung `git add`, `git commit`, dan `git push origin development`.
- **Branch Default untuk checkout:** Selalu `git checkout development` di awal sesi. Jangan ke `main`.
- **Pesan Commit:** Gunakan pesan commit yang deskriptif dalam Bahasa Indonesia, singkat, dan jelas. Contoh: `fix: migrasi gambar ke Cloudinary + perbaikan domain admin`.
- **CONTOH ALUR YANG BENAR:**
  ```
  git checkout development
  git add <files>
  git commit -m "fix: perbaikan X"
  git push origin development
  gh pr create --base main --head development --title "fix: perbaikan X" --body "..."
  gh pr merge <PR_NUMBER> --merge
  ```

## Aturan Wajib Testing & Validasi Sebelum Push

**PRINSIP UTAMA: Setiap perubahan WAJIB diuji sebelum push. Jangan pernah menganggap kode "sudah benar" tanpa verifikasi.**

### Aturan Testing Intensif

- **Test setelah SETIAP perubahan:** Setelah mengubah, menambah, atau memperbaiki kode apa pun, WAJIB langsung menjalankan test untuk memastikan tidak ada error baru yang muncul. Jangan menumpuk banyak perubahan lalu test di akhir — ini menyebabkan error sulit dilacak.
- **Simulasi alur pengguna:** Jangan hanya test syntax. Jalankan alur lengkap seperti yang akan dilakukan pengguna. Contoh: jika menambah endpoint `/reset`, buka browser dan akses endpoint tersebut untuk memastikan benar-benar berfungsi, bukan hanya cek apakah kode bisa di-parse.
- **Deteksi error segera:** Jika terdeteksi error setelah perubahan, segera perbaiki SEBELUM melakukan perubahan lain atau SEBELUM push ke GitHub. Jangan biarkan error mengendap.
- **Test di environment yang benar:** Pastikan test di lokal dulu sebelum deploy. Perbedaan environment (Windows vs Linux, localhost vs cloud) sering menyebabkan error yang tidak terduga.
- **Cek resource locking:** Operasi file system (hapus, rename, write) bisa gagal jika file masih dipakai proses lain. Selalu tutup koneksi/socket/lock SEBELUM operasi file. Contoh: tutup Baileys socket sebelum hapus folder `auth_info`.
- **Jangan asumsikan success:** Setelah push ke production (Vercel), cek apakah deploy berhasil dan fitur berfungsi. Jangan hanya percaya bahwa "kode sudah benar jadi pasti jalan".

### Checklist Testing Wajib

1. **Fungsionalitas CRUD Admin:**
    - Pastikan semua operasi Create, Read, Update, Delete berfungsi di semua tabel (Users, Collections, Achievements, Chatbot)
    - Pastikan upload foto/video berhasil dan gambar muncul di halaman terkait
    - Pastikan modal konfirmasi hapus berfungsi dengan benar
    - Pastikan validasi form menampilkan pesan error yang sesuai

2. **Routing & Middleware:**
    - Pastikan middleware `AdminOnly` memeriksa role admin (bukan hanya autentikasi)
    - Pastikan middleware `AdminDomain` menggunakan redirect 308 (bukan 301) untuk mempreservasi method POST
    - Pastikan tidak ada redirect loop antar domain

3. **Database Compatibility (TiDB):**
    - Pastikan semua migration kompatibel dengan TiDB Cloud
    - Pastikan AUTO_INCREMENT berfungsi di semua tabel
    - Pastikan kolom JSON (metadata) tersimpan dengan benar

4. **Performa:**
    - Pastikan `APP_DEBUG=false` di production
    - Pastikan tidak ada duplikasi loading resource (font, CSS, JS)
    - Pastikan gambar Cloudinary menggunakan transformasi `q_auto,f_auto`
    - Pastikan Firebase/lazy-loaded resources tidak memblok render halaman

5. **Environment Variables:**
    - Pastikan semua env vars yang diperlukan tersedia di `vercel.json` atau Vercel Dashboard
    - Jangan masukkan secrets (API keys, passwords) ke dalam `vercel.json` — gunakan Vercel Dashboard

6. **Error Handling:**
    - Pastikan tidak ada error 500 di halaman manapun
    - Pastikan error handling di JavaScript (fetch/AJAX) menampilkan pesan yang jelas
    - Pastikan `filemtime()` tidak digunakan di template Blade (gagal di Vercel)
