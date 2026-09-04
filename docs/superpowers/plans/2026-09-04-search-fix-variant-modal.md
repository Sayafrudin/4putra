# Rencana: Fix Search Admin Setelah CRUD + Modal Varian ala Daily Activities

**Goal:** (A) Search tabel admin tetap berfungsi setelah CRUD (root-cause fix global); (B) Klik card koleksi ber-varian membuka modal fullscreen ala Daily Activities dengan gambar varian seukuran card induk.

**Architecture:** (A) `table-search.js` menangkap referensi `<tbody>` sekali di load, sedangkan `refreshAdminList()` mengganti `<tbody data-admin-list>` setelah CRUD → referensi yatim. Fix: resolver tbody/empty-row diambil ulang setiap keystroke. (B) Tiru pola Daily Activities: data varian di-build Blade `@php` → `@js()` → Alpine `x-data` di `<section>`, overlay `fixed inset-0 z-[90]`, Escape/backdrop close, scroll-lock.

**Keputusan user:** Modal fullscreen ala Daily Activities (expandable grid dihapus). Prioritas performa: `template x-if` (nol render saat tertutup), JSON minimal, Cloudinary `w_600,q_auto,f_auto`, `loading="lazy"`, tanpa dependensi baru.

## Global Constraints

- Turbo Drive aktif di site publik; Alpine kompatibel (MutationObserver).
- `zoomMedia` global dari `public/js/media-protect.js` (z-9999) — di atas modal (z-90).
- Komparasi ID longgar (TiDB stringify); commit Bahasa Indonesia; push ke development (PR #119 auto-update).

---

### Task 1: Fix `table-search.js` (berlaku SEMUA tabel admin)

- Rewrite `public/js/table-search.js`: wrapper+tbody+emptyRow di-resolve ulang di setiap event input; teks asli baris empty via `dataset.originalText`.
- `resources/views/layouts/admin.blade.php`: cache-busting `?v={{ filemtime(...) }}` untuk table-search.js.
- Verifikasi: `node --check`; manual test admin.

### Task 2: Modal varian (`collections.blade.php`)

- `@php` build `$variantData` (locale + `w_600,c_fill,q_auto,f_auto`).
- `x-data` di `<section>`: `items @js($variantData)`, `open`, `idx`, `openAt(id)` (findIndex + scroll-lock), `close()`.
- Card induk: `@click="openAt('{{ $item->id }}')"`, `aria-haspopup="dialog"`, chevron statis, badge varian tetap. Hapus expandable grid + state `expanded`.
- Modal akhir section: backdrop close, `@keydown.escape.window`, header (scientific merah + nama + X), body scrollable grid `grid-cols-1 sm:grid-cols-2 lg:grid-cols-3` `aspect-[4/5]`, `@click="zoomMedia(v.image)"`.

### Task 3: Test + verifikasi

- Update `CollectionVariantTest::test_public_page_renders_variant_only_inside_parent_grid`: `alt="Uji IRN Unik"` =1, `"name":"Uji IRN Unik"` =1, `"name":"Uji IRN Albino Unik"` =1, `alt="Uji IRN Albino Unik"` =0.
- `php artisan test` full suite + `node --check` kedua JS.

### Task 4: Git

- Commit deskriptif Bahasa Indonesia, push `development` (PR #119 otomatis memuat).
