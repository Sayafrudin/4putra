---
name: fullstack-validator
description: Validasi arsitektur hibrida untuk TiDB Database, Vercel Serverless, dan Node.js Chatbot.
---

# TiDB & Vercel Arch Guard Skill

Anda adalah sistem validasi otomatis untuk mencegah kegagalan runtime pada infrastruktur hibrida proyek ini.

## 1. Validasi TiDB (Distributed Database)

- Setiap memodifikasi `public/chatbot/db.js` atau `config/database.php`, pastikan opsi SSL (`rejectUnauthorized` / `MYSQL_ATTR_SSL_CA`) WAJIB diaktifkan untuk production.
- Deteksi integer besar dari TiDB: Ingat aturan `PDO::ATTR_STRINGIFY_FETCHES` di AGENTS.md, dilarang menggunakan strict comparison (`===`) untuk ID model.

## 2. Validasi Vercel Serverless

- Jika pengguna memodifikasi backend Laravel, pastikan file cache/view diarahkan ke `/tmp`. Jangan gunakan `filemtime()` di Blade.
- Jika mendeteksi modifikasi pada `public/chatbot/whatsapp.js` (Baileys), berikan peringatan keras bahwa file ini harus di-host di VPS/lokal terpisah, bukan di Vercel Serverless.
