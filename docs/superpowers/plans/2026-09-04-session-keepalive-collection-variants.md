# Rencana Implementasi: Session Keep-Alive Fix + Sub-Koleksi (Varian Burung)

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** (A) Klik "Perpanjang Sesi" selalu memperpanjang sesi via AJAX 200 tanpa reload/redirect palsu; (B) Koleksi burung mendukung relasi parent-child (varian/mutasi) di admin + UI publik expandable grid interaktif.

**Architecture:** Reuse endpoint `/admin/ping` yang sudah ada (diperkuat strict-success handling + cache-busting). Varian = kolom `parent_id` self-referencing FK `nullOnDelete` pada tabel `collections`, 1 level kedalaman divalidasi di controller. Publik memakai Alpine.js `x-data` inline (tanpa dependensi baru).

**Tech Stack:** Laravel 11, TiDB (MySQL-compatible, `PDO::ATTR_STRINGIFY_FETCHES`, PK `INT UNSIGNED`), Alpine.js, Tailwind v4, PHPUnit.

**Spec:** Permintaan user (2 prioritas + keputusan klarifikasi: reuse `/admin/ping`, expandable grid inline, varian jadi koleksi utama saat induk dihapus).

## Global Constraints

- TiDB: PK `collections` = `INT UNSIGNED` (hasil rebuild `2026_08_28_000003`) → `parent_id` wajib `unsignedInteger`.
- ID sebagai string (`$keyType='string'`, stringify fetches) → komparasi ID pakai `==`/`assertEquals`, bukan `===`.
- Vercel serverless read-only: gambar via Cloudinary URL (bukan storage lokal); cache file di `/tmp` bersifat ephemeral.
- Admin UI: modal kustom (bukan `confirm()`), validasi JS kustom, `rounded-xl`/`border-gray-700`/bg `#151a22`, tombol aksi `<button>` berwarna (bukan link teks), terminologi UI "Varian".
- Bilingual publik (ID/EN) via `lang/id.json` + `lang/en.json`.
- Commit deskriptif Bahasa Indonesia, push ke `development`, PR ke `main` di akhir.
- Turbo Drive aktif di site publik; admin layout `data-turbo="false"`.

---

## FASE A — Perbaikan Session Keep-Alive

### Task 1: Test regresi ping (TDD — pengaman kontrak)

**Files:**
- Modify: `tests/Feature/AdminSessionPingTest.php`

**Interfaces:**
- Consumes: `GET /admin/ping` → `DashboardController::ping` (sudah ada).
- Produces: kontrak terkunci — 200 JSON `{ok:true, csrf}` + `session.last_activity` tersentuh.

- [ ] **Step 1: Tambahkan test** (menempel pola test yang ada, cleanup manual tanpa `RefreshDatabase`):

```php
public function test_ping_touches_session_last_activity_and_returns_current_csrf(): void
{
    $user = User::updateOrCreate(
        ['email' => 'admin-ping-test@4putra.test'],
        ['name' => 'Admin Ping Test', 'password' => bcrypt('password123'), 'role' => 'admin']
    );

    $response = $this->actingAs($user)->getJson('/admin/ping');

    $response->assertStatus(200)->assertJson(['ok' => true]);
    $this->assertNotNull($response->getSession()->get('last_activity'));
    $this->assertSame($response->getSession()->token(), $response->json('csrf'));

    $user->delete();
}
```

- [ ] **Step 2: Jalankan** — `php artisan test tests/Feature/AdminSessionPingTest.php` — Expected: PASS.

### Task 2: Perkuat `session-timeout.js` (akar masalah: false-success + silent catch)

**Files:**
- Modify: `public/js/session-timeout.js:47-62` (`requestPing`), `:78-98` (`createModal`), `:195-217` (`hideWarning`), `:219-239` (`extendSession`), `:171-179` (copy expired state)

**Interfaces:**
- Produces: `requestPing()` resolve `data.ok=true` (sukses) | `data.expired=true` (401/419) | `data.failed=true` (308 redirect/500/jaringan). `window.sessionTimeout` debug API tetap.

- [ ] **Step 1: Ganti `requestPing`** — sukses HANYA 200 + JSON + `ok:true`:

```js
    function requestPing() {
        return fetch('/admin/ping', {
            method: 'GET', // GET = bebas 419 CSRF palsu; endpoint juga menerima POST
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            credentials: 'same-origin',
        }).then(function (res) {
            if (res.status === 401 || res.status === 419) {
                return { expired: true };
            }
            var ct = res.headers.get('content-type') || '';
            if (res.ok && ct.indexOf('application/json') !== -1) {
                return res.json().then(function (data) {
                    return data && data.ok ? data : { failed: true };
                });
            }
            // 308 redirect (AdminDomain), 500, HTML, dll = BUKAN sukses
            return { failed: true };
        }).catch(function () {
            return { failed: true }; // jaringan putus — tidak lagi ditelan diam-diam
        });
    }
```

- [ ] **Step 2: Tambahkan baris status di `createModal`** — sisipkan sebelum `<div class="flex justify-end gap-3">` di `innerHTML`:

```js
'<p id="session-modal-status" class="hidden text-xs text-red-400 mt-3"></p>' +
```

- [ ] **Step 3: Helper status + ganti `extendSession`**:

```js
    function setStatus(msg) {
        var el = document.getElementById('session-modal-status');
        if (el) {
            el.textContent = msg || '';
            el.classList.toggle('hidden', !msg);
        }
    }

    function setExtending(busy) {
        var btn = document.getElementById('session-extend-btn');
        if (!btn) return;
        btn.disabled = busy;
        if (busy) {
            btn.dataset.label = btn.textContent;
            btn.textContent = 'Memperbarui...';
        } else if (btn.dataset.label) {
            btn.textContent = btn.dataset.label;
        }
    }

    function extendSession(e) {
        if (e && typeof e.preventDefault === 'function') {
            e.preventDefault();
        }
        if (e && typeof e.stopPropagation === 'function') {
            e.stopPropagation();
        }

        setExtending(true);
        setStatus('');
        requestPing().then(function (data) {
            setExtending(false);
            if (data && data.expired) {
                // Sesi benar-benar habis di server → arahkan ke login
                showExpiredState();
                return;
            }
            if (data && data.failed) {
                // Jangan tutup modal pada kegagalan — beri kesempatan retry
                setStatus('Gagal memperbarui sesi. Periksa koneksi lalu klik Perpanjang Sesi lagi.');
                return;
            }
            applyCsrf(data);
            hideWarning(); // sukses: tutup modal + reset timer, TANPA reload halaman
            resetTimer();
        });
    }
```

- [ ] **Step 4: Rapikan `hideWarning` + copy expired state** — `hideWarning()` mulai dengan `setStatus('');`; `showExpiredState()` ubah teks `paragraphs[0].textContent = 'Sesi telah benar-benar habis. Silakan login kembali untuk melanjutkan.'`

- [ ] **Step 5: Verifikasi manual** — klik "Perpanjang Sesi" → `/admin/ping` **200 OK**, modal tertutup tanpa reload; kegagalan jaringan → pesan retry.

### Task 3: Cache-busting script admin

**Files:**
- Modify: `resources/views/layouts/admin.blade.php:34`

- [ ] **Step 1:**

```blade
<script src="{{ asset('js/session-timeout.js') }}?v={{ filemtime(public_path('js/session-timeout.js')) }}" defer></script>
```

- [ ] **Step 2: Commit Fase A**

```bash
git add public/js/session-timeout.js resources/views/layouts/admin.blade.php tests/Feature/AdminSessionPingTest.php
git commit -m "fix(admin): perkuat keep-alive sesi - hanya 200+JSON yang dianggap sukses, feedback retry, cache-busting script"
```

---

## FASE B — Varian Koleksi: Database, Model, Admin

### Task 4: Migration `parent_id`

**Files:**
- Create: `database/migrations/2026_09_04_000001_add_parent_id_to_collections_table.php`

- [ ] **Step 1: Tulis migration:**

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            // INT UNSIGNED agar cocok dengan PK hasil rebuild
            // 2026_08_28_000003_fix_auto_random_pk_to_safe_int
            $table->unsignedInteger('parent_id')->nullable()->after('sort_order');
            $table->foreign('parent_id')
                ->references('id')->on('collections')
                ->nullOnDelete(); // varian naik jadi koleksi utama saat induk dihapus
        });
    }

    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropColumn('parent_id');
        });
    }
};
```

- [ ] **Step 2: Jalankan** — `php artisan migrate`.

### Task 5: Relasi Model

**Files:**
- Modify: `app/Models/Collection.php`

- [ ] **Step 1:** tambah `'parent_id'` ke `$fillable`, lalu:

```php
    public function parent()
    {
        return $this->belongsTo(Collection::class, 'parent_id');
    }

    public function variants()
    {
        return $this->hasMany(Collection::class, 'parent_id')->orderBy('sort_order');
    }
```

### Task 6: Test varian (TDD)

**Files:**
- Create: `tests/Feature/CollectionVariantTest.php`

**Interfaces:**
- Consumes: relasi Task 5, validasi Task 7, view Task 9/11.
- Produces: jaring pengaman untuk seluruh Fase B/C.

- [ ] **Step 1: Tulis test** (cleanup manual, admin via `User::updateOrCreate`, komparasi ID longgar) — 6 test: relasi parent/variants, store varian dengan parent, tolak nesting varian-under-varian + self-parent (422), tolak parent tak dikenal (422), destroy induk → varian jadi top-level, halaman publik render varian hanya di grid induk.

- [ ] **Step 2: Jalankan** — `php artisan test tests/Feature/CollectionVariantTest.php` — Expected: relasi PASS, sisanya FAIL sampai Task 7/10/11 selesai.

### Task 7: Controller admin

**Files:**
- Modify: `app/Http/Controllers/Admin/AdminCollectionController.php` (`index`, `store`, `update`)

**Interfaces:**
- Produces: view menerima `$collections` (flat + `parent_id`) dan `$parents`; validasi `parent_id` 1-level via `variantRules(?Collection $self = null)`.

- [ ] **Step 1: `index()`** — select += `parent_id`, `$parents = $collections->filter(fn ($c) => empty($c->parent_id))->values()`.
- [ ] **Step 2: Method `variantRules()`** — closure: parent ada, bukan self, top-level, belum punya varian.
- [ ] **Step 3: `store()`** — validate `variantRules()`, create += `'parent_id' => $request->filled('parent_id') ? $request->input('parent_id') : null`.
- [ ] **Step 4: `update()`** — validate `variantRules($collection)`; `if ($request->has('parent_id')) { $data['parent_id'] = $request->filled('parent_id') ? $request->input('parent_id') : null; }`.
- [ ] **Step 5: Jalankan** — test Fase B (5 pertama PASS, test publik masih FAIL).

### Task 8: Admin index + form (view)

**Files:**
- Modify: `resources/views/admin/collections/index.blade.php`

- [ ] **Step 1: Tabel tree** — `$variantsByParent = $collections->groupBy(fn ($c) => (string) $c->parent_id)`; loop induk + badge "N Varian" + tombol "+ Varian" (biru); baris varian indent `pl-14` dengan `↳`, aksi Ubah/Hapus.
- [ ] **Step 2: Dropdown "Induk Koleksi" create** — `#create-col-parent` (opsi dari `$parents`), info `#create-col-parent-info`.
- [ ] **Step 3: Dropdown sama di edit** — `#edit-col-parent`.
- [ ] **Step 4: Commit Fase B.**

### Task 9: `collections.js` — integrasi dropdown & prefill

**Files:**
- Modify: `public/js/collections.js`

- [ ] **Step 1: `window.openCreateVariantModal(col)`** — buka create modal, set `#create-col-parent` = col.id, prefill kategori, tampilkan info.
- [ ] **Step 2: Listener change dropdown** — kosongkan info saat value kosong.
- [ ] **Step 3: `openCollectionEditModal`** — set `#edit-col-parent` dari `col.parent_id`, disable opsi self.
- [ ] **Step 4: Verifikasi manual admin.**

---

## FASE C — UI Publik Expandable Grid

### Task 10: Controller publik

**Files:**
- Modify: `app/Http/Controllers/CollectionController.php`

- [ ] **Step 1:** `whereNull('parent_id')->with('variants:id,parent_id,name,name_en,scientific_name,image_path,sort_order')`, cache key tetap `public.collections`.

### Task 11: Blade publik + localization

**Files:**
- Modify: `resources/views/collections.blade.php`, `lang/id.json`, `lang/en.json`, `resources/css/app.css`

- [ ] **Step 1: Lang keys** — `collections.variant_count`: id `1 Varian|:n Varian`, en `1 Variant|:n Variants`.
- [ ] **Step 2: CSS** — `[x-cloak]{display:none!important}`.
- [ ] **Step 3: Blok kartu** — wrapper `x-data="{ expanded: null }"`; card induk `<button>` toggle + badge varian + chevron rotate; grid varian 2 kolom dengan `x-show` + `x-transition` (`transition-all duration-300 ease-in-out`); item tanpa varian tetap `<x-card>`.
- [ ] **Step 4: Jalankan** — `php artisan test tests/Feature/CollectionVariantTest.php` — SEMUA PASS.
- [ ] **Step 5: Commit Fase C.**

---

## Task 12: Verifikasi Menyeluruh

- [ ] `php artisan test` — seluruh suite PASS.
- [ ] `npm run build` — sukses.
- [ ] Checklist AGENTS.md: CRUD & media, routing & caching, TiDB kompat, serverless, error handling AJAX, perilaku sesi.

## Task 13: Git & PR

- [ ] `git push origin development`
- [ ] `gh pr create --base main --head development`
