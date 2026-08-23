---
name: fullstack-validator
description: Trigger automatically whenever modifying database configurations, Eloquent models, controllers, Blade templates, deployment configs, or Node.js chatbot files to prevent TiDB and Vercel serverless runtime failures.
---

# TiDB, Vercel, & Chatbot Architecture Validator

Anda adalah validator arsitektur hibrida otomatis. Jalankan pemeriksaan berikut sebelum menyerahkan perubahan kode apa pun:

## 1. Validasi TiDB Cloud & Database Layer

- **SSL Enkripsi:** Pastikan opsi SSL (`rejectUnauthorized: true` / `PDO::MYSQL_ATTR_SSL_CA`) aktif pada `public/chatbot/db.js` dan `config/database.php` untuk environment production[cite: 1].
- **Format ID (Stringify Fetches):** Karena `PDO::ATTR_STRINGIFY_FETCHES` aktif di konfigurasi database, semua ID model Laravel diperlakukan sebagai string[cite: 1]. Dilarang menggunakan perbandingan ketat (`===`) antar ID, gunakan `==` atau lakukan casting ke string[cite: 1].
- **Anti N+1 Query:** Periksa controller (terutama `CollectionController` dan `AchievementController`). Pastikan query relasi gambar atau kategori selalu menggunakan eager loading `with()`.

## 2. Validasi Vercel Serverless (Read-Only Environment)

- **Filesystem Constraints:** Pastikan penulisan cache, session, dan compiled views diarahkan ke direktori `/tmp`[cite: 1]. Dilarang menulis file lokal secara persisten di luar `/tmp`[cite: 1].
- **Blade Safe Functions:** Dilarang keras menggunakan fungsi `filemtime()` pada template Blade karena akan memicu crash di Vercel[cite: 1].
- **Isolasi Node.js Bot:** Jika mendeteksi perubahan pada `public/chatbot/whatsapp.js` (Baileys), pastikan kode tidak berasumsi berjalan di Vercel Serverless. File socket bot wajib berjalan di lingkungan standalone (VPS/lokal port 3001)[cite: 1].

## 3. Eksekusi Pengujian Mandiri

Sebelum menyatakan validasi selesai, jalankan pengecekan berikut:

1. Jalankan `php artisan test` untuk memastikan tidak ada query atau model yang rusak[cite: 1].
2. Jalankan `npm run build` jika terdapat perubahan pada asset frontend atau Tailwind v4[cite: 1].
3. Periksa file log jika terdapat query lambat atau error koneksi database.
