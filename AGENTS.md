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

## Aturan Kebersihan Data Uji & Performa

- Wajib hapus data dummy/uji setelah selesai mengetes sebuah fitur (mis. card koleksi, portofolio, aktivitas, foto/video yang diupload hanya untuk test). Data uji tidak boleh tertinggal di database agar tidak menumpuk data tidak berguna dan tidak membebani performa situs maupun admin dashboard.
- Jaga performa tetap prioritas: hindari query N+1 (selalu eager loading `with()`), manfaatkan cache (`Cache::remember` + `Cache::forget` saat data berubah), dan jangan menambah beban render yang tidak perlu.

## Checklist Verifikasi & Testing Mandatory

Setiap modifikasi wajib melewati validasi berikut sebelum diselesaikan:

1. CRUD & Media: Create, Read, Update, Delete berjalan normal. Upload file berhasil dan dirender.
2. Routing & Caching: Middleware `AdminOnly` berfungsi. Tidak ada loop redirect. Turbo Drive tidak mengalami cache collision pada pergantian bahasa.
3. Database TiDB: Migrasi kompatibel. `PDO::ATTR_STRINGIFY_FETCHES` aktif. Hindari komparasi strict (`===`) pada ID. Package `colopl/laravel-tidb` TELAH DIHAPUS karena Blueprint override-nya membuat `$table->id()` menghasilkan kolom tanpa primary key/auto_increment pada koneksi driver `mysql` — setelah membuat tabel baru, verifikasi `SHOW CREATE TABLE` memuat `AUTO_INCREMENT` + `PRIMARY KEY`.
4. Performa Serverless: `APP_DEBUG=false`. File statis dilayani oleh Vercel Edge CDN dengan Cache-Control yang tepat. File statis baru di `public/` (robots.txt, sitemap.xml, favicon.ico, dll) WAJIB didaftarkan di route map `vercel.json` — route hanya memetakan daftar direktori eksplisit, tanpa itu URL-nya masuk ke PHP (404).
5. Error Handling: Penanganan AJAX error menampilkan pesan jelas. Bebas error 500.
6. Smoke Render Halaman: Setiap Blade yang diubah wajib diakses sungguhan via HTTP (bukan hanya lulus test suite) untuk memastikan tidak ada ParseError di view — komentar `{{-- --}}` dilarang di dalam blok `@php ... @endphp` (gunakan komentar PHP `//`).

## Protokol Testing Intensif Wajib (Anti-Error Menjalar)

Setiap fitur yang dirubah/diperbaiki/ditambahkan wajib lulus SEMUA lapisan ini sebelum commit — hasil harus diverifikasi dari SISI SERVER (status code, state database, cookie), bukan hanya tampilan UI (bukan gimmick):

1. **PHPUnit Penuh**: `php artisan test` keseluruhan suite (bukan hanya test yang terkait perubahan), tanpa skip.
2. **Smoke Render HTTP**: setiap Blade yang disentuh diakses sungguhan via HTTP dengan server berjalan.
3. **E2E Browser Nyata** (wajib untuk fitur interaktif: modal, tombol, AJAX, session): gunakan `npm run test:e2e` (`tests/e2e/session-flow.mjs`, playwright-core + Edge terpasang). Tombol yang berubah perilaku WAJIB diklik sungguhan dan efek sisi server diverifikasi (contoh: klik "Perpanjang Sesi" → ping 200 + sesi benar-benar diperpanjang — bukti: siklus popup kedua muncul setelah idle melewati lifetime lagi).
4. **E2E Sesi/Auth dengan Server Berjalan**: uji dengan cookie asli + lifetime diperpendek via env (mis. `SESSION_LIFETIME=1`), bukan simulasi cache. Skenario wajib: login → expire → perilaku pemulihan → redirect balik ke halaman asal (intended URL).
5. **Audit Performa**: tidak menambah N+1 (eager `with()`), cache tetap hit, ukur waktu load halaman utama saat E2E (< 3 detik lokal). Script E2E mencatat NavigationTiming.
6. **Kebersihan Data Uji**: semua user/entri CRUD/foto dummy yang dibuat saat testing wajib dihapus dari database.
7. **Pasca-Deploy**: setelah merge ke `main`, cek status deploy Vercel dan uji ulang alur inti (login, halaman admin, switch bahasa) di URL produksi.

## Aturan Chatbot WhatsApp (Baileys) — Wajib

1. **Clock skew TiDB**: Jam DB TiDB TERTINGGAL beberapa jam dari jam Node (`NOW()` ≈ jam nyata − 7 jam, terukur 2026-09-21). DILARANG membandingkan timestamp DB dengan jam Node di JS (`Date.now() - new Date(kolom_db)` salah selalu). Komparasi durasi (rate limit, idle reset) WAJIB dihitung SQL-side: `TIMESTAMPDIFF(SECOND, kolom, NOW())`. Untuk TAMPILAN tanggal di WhatsApp, gunakan offset terukur (`dapatkanOffsetDb` di `whatsapp.js`) + format `timeZone: 'UTC'` setelah koreksi +7 jam WIB.
2. **Tombol WA floating hanya localhost**: Komponen `components/site/whatsapp.blade.php` wajib dibungkus `@if(app()->environment('local')) ... @endif` — tombol TIDAK BOLEH muncul di produksi/Vercel (tugas akhir hanya memakai localhost). Jangan hapus guard ini saat refactor; merge ke main harus tetap menyembunyikannya.
3. **Rahasia di luar folder publik**: `chatbot.env` wajib berada di `storage/app/` (BUKAN `public/chatbot/.env`) dan sesi Baileys `auth_info` wajib di `storage/app/chatbot-auth/`. `php artisan serve` menyajikan file mentah di bawah `public/` — apa pun di `public/` dapat diunduh. `vercel.json` wajib memblok `/public/chatbot/(.*)` → 404.
4. **Perubahan state machine**: state `sesi_aktif` (menu/ai/human/inventory_select/checkout_qty) hanya boleh diubah di handler state masing-masing; input angka jangan dibajak lintas state. Setiap pesan masuk diproses serial per-chat via `antrePesan`.

## Aturan Keamanan Wajib (Auth, Session, Database)

1. Rate limit semua endpoint autentikasi (`POST /login` → `throttle:5,1`). Jangan dihapus saat refactor routing.
2. Percobaan login gagal wajib ter-log (`Log::warning` — email + IP, dilarang menyimpan password).
3. `TrustProxies(at: '*')` wajib tetap ada di `bootstrap/app.php` (Vercel = semua trafik lewat proxy; tanpa ini rate limit per-IP salah).
4. Security headers (`SecurityHeaders` middleware: X-Frame-Options, X-Content-Type-Options, Referrer-Policy) wajib pada semua response web.
5. Koneksi TiDB: SSL wajib; jika `DB_SSL_CA` tersedia, verifikasi sertifikat wajib aktif (`PDO::MYSQL_ATTR_SSL_VERIFY_SERVER_CERT => (bool) env('DB_SSL_CA')`).
6. Cookie remember-me (14 hari, selalu aktif): logout manual maupun timeout wajib menghapusnya (`Auth::logout` → cycleRememberToken). Jangan pernah membuat jalur login yang tidak meregenerasi session.
7. Dilarang menurunkan `APP_DEBUG=false` produksi atau mengaktifkan debug output di response.
8. **Rahasia**: dilarang hardcode secret/API key (GROQ, Midtrans, Meta, Firebase) di kode maupun file JS yang di-bundle; semua via env. `.env*` tidak pernah masuk git (verifikasi `git ls-files` tidak memuat `.env`), dilarang menaruh `.env` di bawah `public/`, log tidak boleh mencetak secret, dilarang menaruh secret di client-side JS. Midtrans pakai server-key di backend saja.
9. **Input & injeksi**: semua request divalidasi (`$request->validate`) + sanitasi; query wajib binding/Eloquent (larang string concatenation di `whereRaw`/`DB::statement`); output via `{{ }}` escaping (larang `{!! !!}` untuk input user); laravel tidak pakai NoSQL — tapi input yang masuk ke modul Node chatbot juga wajib dibind (mysql2 `execute`).
10. **Auth & authz server-side**: semua rute `/admin/*` wajib middleware `AdminOnly`; cek kepemilikan data (cross-user access dilarang); role admin diverifikasi di server, dilarang hanya di UI; alur reset password wajib token sekali-pakai + throttle.
11. **Upload**: validasi `mimes` + ukuran maksimum, nama file acak, kompres/gunakan format efisien, dirender via symlink storage. Scan antivirus di serverless tidak realistis (ponytail ceiling: whitelist MIME + re-encode gambar bila perlu).
12. **Abuse**: rate limit endpoint publik yang menulis (kontak, AJAX); API chatbot punya rate limit per pelanggan (`cekRateLimit`); spending caps Midtrans: total harga transaksi divalidasi server-side dari DB (bukan dari input client) — harga jangan pernah dihitung dari payload user.
13. **Idempotensi**: tombol submit admin dilarang double-submit (disable saat loading); webhook Midtrans wajib cek `status` transaksi sebelum memproses ulang (duplicate notification = no-op); `midtrans_order_id` unik.
14. **Database**: DB TiDB tidak publik (SSL + kredensial di env); index wajib untuk kolom filter/ORDER yang sering; query berat pakai `paginate()`; larang N+1 (`with()`); `Cache::remember` untuk halaman publik berat.
15. **Resiliensi & UX**: setiap AJAX punya handler error dengan pesan jelas; loading state saat menunggu; empty state saat data kosong; handle failed request & API timeout (midtrans.js: timeout + retry sesuai kebutuhan); jangan biarkan request menggantung.
16. **Operasional**: error logging aktif (`LOG_CHANNEL=stderr` di Vercel); uptime bisa dipantau via health endpoint (`/health` chatbot, halaman utama) + Vercel checks; backup TiDB diuji restore minimal 1× (catat hasil); uji konkurensi ringan sebelum demo (2-3 user simultan di alur inti).

## Checklist Anti-AI-Slop & SEO (Situs Publik)

Situs publik TIDAK BOLEH terlihat seperti hasil AI. Wajib:

1. `<title>` unik per halaman (bukan satu judul statis untuk semua).
2. `<meta name="description">` per halaman.
3. Open Graph lengkap (og:title, og:description, og:image, og:url).
4. JSON-LD structured data (Organization di home).
5. Tepat SATU `<h1>` per halaman.
6. `<link rel="canonical">` per halaman.
7. `public/llms.txt` ada (deskripsi situs untuk AI crawler).
8. `robots.txt` TIDAK memblokir AI crawler + memuat baris `Sitemap:`.
9. Favicon ada + `<link rel="icon">` di layout.
10. `public/sitemap.xml` ada (daftar halaman publik).
11. `<html lang="id|en">` dinamis sesuai locale.
12. Semua `<img>` punya `alt` bermakna.
13. Tanpa source maps di build produksi (`vite build` sourcemap=false).
14. Bebas console error (cek via E2E).
15. Bundle JS ramping (chunk firebase/alpine terpisah; jangan muat chat.js/firebase di halaman yang tak memakainya).
16. Halaman 404 kustom tersedia & dirender.
17. SSR Blade — view source harus menampilkan konten (dilarang render-only-client).
18. Catatan: URL vercel.app masih dipakai; bila domain kustom (GoDaddy) sudah diarahkan, ganti `APP_URL` + og:url/canonical.

## Tooling & Visualisasi

- `laramint/laravel-brain` (dev dependency): `php artisan brain:scan` → viewer interaktif `/_laravel-brain` (request lifecycle, class diagram, ERD/schema DB, route security view, export Mermaid/PNG). Dev-only — JANGAN ikut ter-install di Vercel (composer --no-dev), JANGAN biarkan overwrite AGENTS.md via `brain:generate-rules`.
- Skill `antislop` (6 skill di `.opencode/skills/`): filter anti-AI-slop untuk UI, copywriting, aksesibilitas, layout mobile, dan komentar kode.

## Protokol Orkestrasi Skill Otonom

Sistem wajib memicu skill berikut secara mandiri berdasarkan konteks fase pekerjaan:

1. Fase Inisiasi & Perencanaan:
   `using-superpowers`, `brainstorming`, `grill-me`, `writing-plans`, `find-skills`.
2. Fase Frontend & Visual UI:
   `ui-ux-pro-max`, `impeccable`, `frontend-design`, `antislop`, `antislop-ui`, `antislop-copywriting`, `antislop-human`, `antislop-layoutmobile`, `antislop-code`.
3. Fase Eksekusi & Backend (KISS Principle):
   `ponytail`, `codebase-design`, `test-driven-development`, `executing-plans`, `using-git-worktrees`, `subagent-driven-development`, `dispatching-parallel-agents`.
4. Fase Debugging & Validasi:
   `systematic-debugging`, `fullstack-validator`, `laravel-brain` (visualisasi lifecycle/schema untuk triase).
5. Fase Tinjauan Kualitas & Finalisasi:
   `antislop` (audit UI/copy), `no-ai-slop`, `ponytail-review`, `ponytail-audit`, `ponytail-debt`, `ponytail-gain`, `requesting-code-review`, `receiving-code-review`, `improve-codebase-architecture`, `verification-before-completion`, `finishing-a-development-branch`, `customize-opencode`, `writing-skills`, `ponytail-help`.
