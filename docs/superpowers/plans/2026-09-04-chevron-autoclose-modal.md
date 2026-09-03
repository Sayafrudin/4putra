# Rencana: Hapus Chevron Card Induk + Header Modal Center + Auto-Close Modal Admin

**Goal:** (A) Card induk koleksi publik tanpa chevron (badge varian jadi satu-satunya affordance); (B) Header modal varian terpusat + lebih besar; (C) Modal admin tertutup otomatis setelah create/update sukses di semua halaman.

**Keputusan user:** chevron dihapus; header modal center + diperbesar (ala detail iOS); setelah CRUD sukses langsung balik ke tabel.

## Perubahan

### Task 1+2: `resources/views/collections.blade.php`
- Hapus SVG chevron di card induk (badge "N Varian" tetap).
- Header modal: `text-center`, X absolute kanan-atas, scientific `text-xs sm:text-sm tracking-[0.25em]`, nama `text-2xl sm:text-3xl`, `px-14 sm:px-20` anti-tabrakan.

### Task 3: Auto-close modal (4 JS)
- `collections.js`: create → `closeModal('create-collection')`, edit → `closeModal('edit-collection')`.
- `achievements.js`: create → `closeModal('create')`, edit → `closeModal('edit')`.
- `daily-activities.js` (submitWithUploads): `closeModal(formEl.id === 'form-create-daily-activities' ? 'create' : 'edit')`.
- `facilities.js` (submitWithUploads): `closeModal(formEl.id === 'form-create-facilities' ? 'create' : 'edit')`.
- Delete: sudah otomatis via `admin-refresh.js` (`form.closest('[id^="modal-"]')`).

### Task 4: Cache-busting include JS di 4 blade admin
`?v={{ filemtime(public_path('js/...')) }}` untuk achievements/daily-activities/facilities/collections index.

## Verifikasi
- `node --check` 4 JS OK; `php artisan view:cache` OK; suite 35/36 PASS (LocaleSwitchTest gagal pre-existing, terverifikasi di baseline 10d3fef).
