## Ringkasan

Lanjutan batch fitur farm — 3 batch sekaligus:

### Batch 3a — Export Kandang & Aviary

- PDF (DomPDF) + Excel (exceljs client-side) untuk papan kandang dan aviary
- DATA_EKSPOR dihitung di controller (bukan inline Blade — bug kompile Blade beres)

### Batch 3b — Search chip burung

- Chip burung kini punya `data-ring`, `data-dna`, `data-fase`, `data-gender`
- Search kandang & aviary memfilter pool + chip yang terpasang (sebelumnya search kandang tidak jalan)

### Batch 3c — Sesi ala MyBCA

- Tombol "Perpanjang Sesi" dihapus; kembali ke tab setelah idle > batas → modal "Sesi Anda Telah Berakhir" + auto-logout server-side
- `session-timeout.js` ditulis ulang (483 → 231 baris); skenario E2E session-flow ditulis ulang (S2–S5)

### Farm Lama & Baru

- Kolom `lokasi` (baru|lama) pada `kandangs` + tabel `burung_pindahs` (riwayat perpindahan burung)
- Sidebar: Manajemen Farm → Management Farm Baru; grup baru Management Farm Lama (menu Kandang)
- Tombol + Tambah Kandang di kedua farm; pool burung share antar farm; riwayat tampil di modal edit burung

## Verifikasi

- PHPUnit penuh: **203 passed** (844 assertions)
- E2E lengkap (session-flow, back-flow, kandang-flow, admin-mobile, role-user-403): **SEMUA LULUS** (exit 0)
- S7 performa: warm-up pass sebelum pengukuran (flapping TiDB cold tidak lagi menggagalkan)
