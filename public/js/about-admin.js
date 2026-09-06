/**
 * about-admin.js
 * Seksi "Manajemen About Us" di halaman admin Achievements:
 * - Ubah media hero: upload Dropzone (foto/video -> Cloudinary) ATAU link eksternal (GDrive/IG/TikTok/YouTube/dll -> embed)
 * - CRUD Leadership (tabel leaderships) dengan Dropzone foto
 * Data & URL dari window.AboutAdminConfig.
 */

(function () {
    'use strict';

    var CFG = window.AboutAdminConfig || {};

    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }

    function csrf() {
        return { 'X-CSRF-TOKEN': CFG.csrfToken };
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

    // =============================================
    // DROPZONE (lazy init saat modal pertama dibuka)
    // =============================================
    var dzMedia = null;
    var dzLeader = null;
    var dzMediaInit = false;
    var dzLeaderInit = false;

    function initDropzones() {
        var Dropzone = window.Dropzone;
        if (!Dropzone) { console.warn('[INFO] Dropzone tidak ter-load.'); return; }
        Dropzone.autoDiscover = false;

        if (!dzMediaInit) {
            dzMedia = new Dropzone('#dropzone-about-media', {
                url: '/',
                autoProcessQueue: false,
                paramName: 'media',
                maxFiles: 1,
                maxFilesize: 50,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mov,video/webm,video/avi',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto/video ke sini atau klik untuk memilih',
                dictRemoveFile: 'Hapus',
                dictMaxFilesExceeded: 'Maksimal 1 file. Hapus file yang ada terlebih dahulu.',
            });
            dzMediaInit = true;
        }

        if (!dzLeaderInit) {
            dzLeader = new Dropzone('#dropzone-leader-photo', {
                url: '/',
                autoProcessQueue: false,
                paramName: 'photo',
                maxFiles: 1,
                maxFilesize: 10,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik foto ke sini atau klik untuk memilih',
                dictRemoveFile: 'Hapus',
                dictMaxFilesExceeded: 'Maksimal 1 foto. Hapus foto yang ada terlebih dahulu.',
            });
            dzLeaderInit = true;
        }
    }

    // =============================================
    // MEDIA HERO
    // =============================================
    window.openMediaModal = function () {
        showErr('about-media-error', '');
        var link = document.getElementById('about-media-link');
        if (link) link.value = '';
        if (dzMedia) { try { dzMedia.removeAllFiles(true); } catch (e) {} }
        hideModal('about-media');
        showModal('about-media');
        requestAnimationFrame(initDropzones);
    };

    window.submitAboutMedia = function () {
        var link = (document.getElementById('about-media-link')?.value || '').trim();
        var file = (dzMedia && dzMedia.getAcceptedFiles()) ? dzMedia.getAcceptedFiles()[0] : null;

        if (!file && !link) {
            showErr('about-media-error', 'Isi link video eksternal atau upload file foto/video terlebih dahulu.');
            return;
        }

        showErr('about-media-error', '');

        if (file) {
            // File dipilih -> prioritas upload ke Cloudinary
            if (!window.uploadToCloudinary) {
                showErr('about-media-error', 'Fungsi upload tidak tersedia. Muat ulang halaman.');
                return;
            }
            setBusy('about-media-submit-btn', true, 'Mengupload...');
            window.uploadToCloudinary(file, 'about', null)
                .then(function (r) {
                    return saveMedia(r.resource_type, r.url);
                })
                .catch(function (err) {
                    setBusy('about-media-submit-btn', false);
                    showErr('about-media-error', (err && err.message) || 'Gagal mengupload file.');
                    showToast('error', 'Gagal', 'Upload file gagal.');
                });
        } else {
            // Hanya link -> embed
            setBusy('about-media-submit-btn', true, 'Menyimpan...');
            saveMedia('embed', link);
        }
    };

    function saveMedia(mediaType, mediaPath) {
        return fetch(CFG.mediaUpdateUrl, {
            method: 'POST',
            headers: Object.assign({ 'Content-Type': 'application/json', 'Accept': 'application/json' }, csrf()),
            body: JSON.stringify({ media_type: mediaType, media_path: mediaPath }),
        })
            .then(function (res) { return res.json(); })
            .then(function (json) {
                if (!json.success) throw new Error(json.message || 'Gagal menyimpan media');
                showToast('success', 'Berhasil', json.message || 'Media About Us berhasil diperbarui');
                setTimeout(function () { location.reload(); }, 800);
            })
            .catch(function (err) {
                setBusy('about-media-submit-btn', false);
                showErr('about-media-error', err.message || 'Terjadi kesalahan saat menyimpan.');
                showToast('error', 'Gagal', err.message || 'Terjadi kesalahan.');
            });
    }

    // =============================================
    // LEADERSHIP CRUD
    // =============================================
    window.openLeaderModal = function (data) {
        var isEdit = !!data;
        showErr('leader-error', '');

        document.getElementById('leader-id').value = isEdit ? data.id : '';
        document.getElementById('leader-name').value = isEdit ? data.name : '';
        document.getElementById('leader-role').value = isEdit ? data.role : '';
        document.getElementById('leader-role-en').value = (isEdit && data.role_en) ? data.role_en : '';
        document.getElementById('leader-sort').value = isEdit ? data.sort_order : '0';
        document.getElementById('leader-photo-hint').textContent = isEdit ? '(opsional, kosongkan jika tetap)' : '(*wajib)';

        var prev = document.getElementById('leader-photo-preview');
        var prevLabel = document.getElementById('leader-photo-preview-label');
        prev.classList.add('hidden');
        prevLabel.classList.add('hidden');
        if (isEdit && data.photo_url) {
            prev.src = data.photo_url;
            prev.classList.remove('hidden');
            prevLabel.classList.remove('hidden');
        }

        hideModal('leader');
        showModal('leader');
        requestAnimationFrame(function () {
            initDropzones();
            if (dzLeader) { try { dzLeader.removeAllFiles(true); } catch (e) {} }
        });
    };

    window.submitLeader = function () {
        var id = document.getElementById('leader-id').value;
        var isEdit = !!id;
        var name = document.getElementById('leader-name').value.trim();
        var role = document.getElementById('leader-role').value.trim();
        var roleEn = document.getElementById('leader-role-en').value.trim();
        var sort = document.getElementById('leader-sort').value || '0';
        var file = (dzLeader && dzLeader.getAcceptedFiles()) ? dzLeader.getAcceptedFiles()[0] : null;

        // Validasi kustom (konvensi: tanpa dependensi validasi bawaan HTML5)
        if (!name) { showErr('leader-error', 'Nama wajib diisi.'); return; }
        if (!role) { showErr('leader-error', 'Role (ID) wajib diisi.'); return; }
        if (!isEdit && !file) { showErr('leader-error', 'Foto wajib diupload.'); return; }

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

        if (file) {
            if (!window.uploadToCloudinary) {
                showErr('leader-error', 'Fungsi upload tidak tersedia. Muat ulang halaman.');
                return;
            }
            setBusy('leader-submit-btn', true, 'Mengupload foto...');
            window.uploadToCloudinary(file, 'about/leadership', null)
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
    var deleteId = null;

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
