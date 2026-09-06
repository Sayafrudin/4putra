/**
 * about-admin.js
 * Seksi "Manajemen About Us" di halaman admin Achievements:
 * - Ubah media hero (foto/video, upload client-side ke Cloudinary)
 * - CRUD Leadership (tabel leaderships)
 * Data & URL dari window.AboutAdminConfig.
 */

(function () {
    'use strict';

    var CFG = window.AboutAdminConfig || {};

    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }

    function showModal(id) {
        var modal = document.getElementById('modal-' + id);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function hideModal(id) {
        var modal = document.getElementById('modal-' + id);
        if (!modal) return;
        modal.classList.remove('flex');
        modal.classList.add('hidden');
    }

    function csrf() {
        return { 'X-CSRF-TOKEN': CFG.csrfToken };
    }

    function showErr(id, msg) {
        var el = document.getElementById(id);
        if (!el) return;
        if (msg) { el.textContent = msg; el.classList.remove('hidden'); }
        else { el.classList.add('hidden'); }
    }

    function setBusy(btnId, busy, busyText) {
        var btn = document.getElementById(btnId);
        if (!btn) return;
        btn.disabled = busy;
        if (busy) { btn.dataset.label = btn.textContent; btn.textContent = busyText || 'Memproses...'; }
        else if (btn.dataset.label) { btn.textContent = btn.dataset.label; }
    }

    // File terpilih per modal
    var mediaFile = null;
    var leaderFile = null;
    var deleteId = null;

    // =============================================
    // MEDIA HERO
    // =============================================
    window.openMediaModal = function () {
        mediaFile = null;
        showErr('about-media-error', '');
        var input = document.getElementById('about-media-file');
        input.value = '';
        var preview = document.getElementById('about-media-preview');
        preview.classList.add('hidden');
        preview.innerHTML = '';
        hideModal('about-media');
        showModal('about-media');
    };

    document.getElementById('about-media-file')?.addEventListener('change', function () {
        mediaFile = this.files && this.files[0] ? this.files[0] : null;
        showErr('about-media-error', '');
        var preview = document.getElementById('about-media-preview');
        preview.innerHTML = '';
        if (!mediaFile) { preview.classList.add('hidden'); return; }
        var url = URL.createObjectURL(mediaFile);
        if (mediaFile.type.indexOf('video') === 0) {
            preview.innerHTML = '<video src="' + url + '" muted controls class="max-h-48 rounded-lg border border-gray-700"></video>';
        } else {
            preview.innerHTML = '<img src="' + url + '" class="h-40 w-32 object-cover rounded-lg border border-gray-700">';
        }
        preview.classList.remove('hidden');
    });

    window.submitAboutMedia = function () {
        if (!mediaFile) {
            showErr('about-media-error', 'Pilih file foto/video terlebih dahulu.');
            return;
        }
        if (!window.uploadToCloudinary) {
            showErr('about-media-error', 'Fungsi upload tidak tersedia. Muat ulang halaman.');
            return;
        }
        showErr('about-media-error', '');
        setBusy('about-media-submit-btn', true, 'Mengupload...');

        window.uploadToCloudinary(mediaFile, 'about', null)
            .then(function (r) {
                return fetch(CFG.mediaUpdateUrl, {
                    method: 'POST',
                    headers: Object.assign({ 'Content-Type': 'application/json', 'Accept': 'application/json' }, csrf()),
                    body: JSON.stringify({ media_type: r.resource_type, media_path: r.url }),
                }).then(function (res) { return res.json(); });
            })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Gagal menyimpan media');
                showToast('success', 'Berhasil', json.message || 'Media About Us berhasil diperbarui');
                setTimeout(function () { location.reload(); }, 800);
            })
            .catch(function (err) {
                setBusy('about-media-submit-btn', false);
                showErr('about-media-error', err.message || 'Terjadi kesalahan saat upload/simpan.');
                showToast('error', 'Gagal', err.message || 'Terjadi kesalahan.');
            });
    };

    // =============================================
    // LEADERSHIP CRUD
    // =============================================
    window.openLeaderModal = function (data) {
        var isEdit = !!data;
        leaderFile = null;
        showErr('leader-error', '');

        document.getElementById('leader-id').value = isEdit ? data.id : '';
        document.getElementById('leader-name').value = isEdit ? data.name : '';
        document.getElementById('leader-role').value = isEdit ? data.role : '';
        document.getElementById('leader-role-en').value = (isEdit && data.role_en) ? data.role_en : '';
        document.getElementById('leader-sort').value = isEdit ? data.sort_order : '0';
        var photo = document.getElementById('leader-photo');
        photo.value = '';
        document.getElementById('leader-photo-hint').textContent = isEdit ? '(opsional, kosongkan jika tetap)' : '(*wajib)';
        var prev = document.getElementById('leader-photo-preview');
        prev.classList.add('hidden');
        if (isEdit && data.photo_path) {
            var url = String(data.photo_path).indexOf('http') === 0 ? data.photo_path : data.photo_path;
            prev.src = url;
            prev.classList.remove('hidden');
        }
        document.getElementById('leader-modal-title').innerHTML =
            '<span class="w-2 h-2 bg-[#E62C37]"></span> ' + (isEdit ? 'Ubah Management' : 'Tambah Management');
        showModal('leader');
    };

    document.getElementById('leader-photo')?.addEventListener('change', function () {
        leaderFile = this.files && this.files[0] ? this.files[0] : null;
        showErr('leader-error', '');
        var prev = document.getElementById('leader-photo-preview');
        if (leaderFile) {
            prev.src = URL.createObjectURL(leaderFile);
            prev.classList.remove('hidden');
        }
    });

    window.submitLeader = function () {
        var id = document.getElementById('leader-id').value;
        var isEdit = !!id;
        var name = document.getElementById('leader-name').value.trim();
        var role = document.getElementById('leader-role').value.trim();
        var roleEn = document.getElementById('leader-role-en').value.trim();
        var sort = document.getElementById('leader-sort').value || '0';

        // Validasi kustom (konvensi: tanpa dependensi validasi bawaan HTML5)
        if (!name) { showErr('leader-error', 'Nama wajib diisi.'); return; }
        if (!role) { showErr('leader-error', 'Role (ID) wajib diisi.'); return; }
        if (!isEdit && !leaderFile) { showErr('leader-error', 'Foto wajib diupload.'); return; }

        var doSave = function (photoPath) {
            setBusy('leader-submit-btn', true, 'Menyimpan...');
            var payload = { name: name, role: role, role_en: roleEn, sort_order: sort };
            if (photoPath) payload.photo_path = photoPath;

            var url = isEdit ? CFG.leadersBaseUrl + '/' + id : CFG.leaderStoreUrl;
            var method = isEdit ? 'PUT' : 'POST';

            fetch(url, {
                method: method,
                headers: Object.assign({ 'Content-Type': 'application/json', 'Accept': 'application/json' }, csrf()),
                body: JSON.stringify(payload),
            })
                .then(function (res) { return res.json(); })
                .then(function (json) {
                    if (!json.success) throw new Error(json.message || 'Gagal menyimpan leadership');
                    showToast('success', 'Berhasil', json.message);
                    setTimeout(function () { location.reload(); }, 800);
                })
                .catch(function (err) {
                    setBusy('leader-submit-btn', false);
                    showErr('leader-error', err.message || 'Terjadi kesalahan.');
                    showToast('error', 'Gagal', err.message || 'Terjadi kesalahan.');
                });
        };

        if (leaderFile) {
            if (!window.uploadToCloudinary) {
                showErr('leader-error', 'Fungsi upload tidak tersedia. Muat ulang halaman.');
                return;
            }
            setBusy('leader-submit-btn', true, 'Mengupload foto...');
            window.uploadToCloudinary(leaderFile, 'about/leadership', null)
                .then(function (r) { doSave(r.url); })
                .catch(function (err) {
                    setBusy('leader-submit-btn', false);
                    showErr('leader-error', (err && err.message) || 'Gagal mengupload foto.');
                    showToast('error', 'Gagal', 'Upload foto gagal.');
                });
        } else {
            doSave('');
        }
    };

    // =============================================
    // LEADERSHIP DELETE
    // =============================================
    window.openLeaderDeleteModal = function (id, name) {
        deleteId = id;
        document.getElementById('leader-delete-name').textContent = name;
        showModal('confirm-delete-leader');
    };

    document.getElementById('confirm-delete-leader-btn')?.addEventListener('click', function () {
        if (!deleteId) return;
        setBusy('confirm-delete-leader-btn', true, 'Menghapus...');
        fetch(CFG.leadersBaseUrl + '/' + deleteId, {
            method: 'DELETE',
            headers: Object.assign({ 'Accept': 'application/json' }, csrf()),
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Gagal menghapus');
                showToast('success', 'Berhasil', json.message);
                setTimeout(function () { location.reload(); }, 800);
            })
            .catch(function (err) {
                setBusy('confirm-delete-leader-btn', false);
                showToast('error', 'Gagal', err.message || 'Terjadi kesalahan.');
            });
    });
})();
