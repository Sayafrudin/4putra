(function () {
    'use strict';

    var CFG = window.CollectionsConfig || {};
    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }
    var els = {
        formCreate: document.getElementById('form-create-collection'),
        formEdit: document.getElementById('form-edit-collection'),
        formDelete: document.getElementById('form-delete-collection'),
    };

    var dzCreate = null;
    var dzEdit = null;
    var dzCreateInitialized = false;
    var dzEditInitialized = false;
    var pendingDeletePhotoEl = null;

    // =============================================
    // MODAL HELPERS (optimized — no animation overhead)
    // =============================================
    function showModal(type) {
        var modal = document.getElementById('modal-' + type);
        if (!modal) return;
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(type) {
        var modal = document.getElementById('modal-' + type);
        if (!modal) return;
        modal.classList.remove('flex');
        modal.classList.add('hidden');
        document.querySelectorAll('[id^="error-"]').forEach(function (el) { el.classList.add('hidden'); });
    }

    window.closeModal = closeModal;

    // =============================================
    // DROPZONE — lazy init (setelah modal visible)
    // =============================================
    function initDzCreate() {
        if (dzCreateInitialized) return;
        var Dropzone = window.Dropzone;
        if (!Dropzone) { console.warn('[INFO] Dropzone tidak ter-load.'); return; }
        Dropzone.autoDiscover = false;

        dzCreate = new Dropzone('#dz-collection-create', {
            url: CFG.storeUrl,
            autoProcessQueue: false,
            uploadMultiple: false,
            paramName: 'image',
            maxFilesize: 2,
            acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
            addRemoveLinks: true,
            dictDefaultMessage: 'Tarik file foto ke sini atau klik untuk memilih',
            dictRemoveFile: 'Hapus',
            headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
        });
        dzCreate.on('sending', function (file, xhr, fd) {
            var formEls = els.formCreate.elements;
            for (var i = 0; i < formEls.length; i++) {
                var el = formEls[i];
                if (!el.name || el.type === 'file') continue;
                fd.append(el.name, el.value);
            }
        });
        dzCreate.on('success', function () {
            showToast('success', 'Berhasil!', 'Koleksi baru telah ditambahkan.');
            setTimeout(function () { location.reload(); }, 1000);
        });
        dzCreate.on('error', function (file, res) {
            console.error('Dropzone create error:', res);
            showToast('error', 'Gagal!', 'Terjadi kesalahan saat upload.');
        });
        dzCreateInitialized = true;
    }

    function initDzEdit() {
        if (dzEditInitialized) return;
        var Dropzone = window.Dropzone;
        if (!Dropzone) return;
        Dropzone.autoDiscover = false;

        dzEdit = new Dropzone('#dz-collection-edit', {
            url: '/',
            autoProcessQueue: false,
            uploadMultiple: false,
            paramName: 'image',
            maxFilesize: 2,
            acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
            addRemoveLinks: true,
            dictDefaultMessage: 'Tarik file foto baru ke sini',
            dictRemoveFile: 'Hapus',
            headers: { 'X-CSRF-TOKEN': CFG.csrfToken },
        });
        dzEdit.on('sending', function (file, xhr, fd) {
            var formEls = els.formEdit.elements;
            for (var i = 0; i < formEls.length; i++) {
                var el = formEls[i];
                if (!el.name || el.type === 'file') continue;
                fd.append(el.name, el.value);
            }
        });
        dzEdit.on('success', function () {
            showToast('success', 'Berhasil!', 'Data koleksi diperbarui.');
            setTimeout(function () { location.reload(); }, 1000);
        });
        dzEdit.on('error', function (file, res) {
            console.error('Dropzone edit error:', res);
            showToast('error', 'Gagal!', 'Terjadi kesalahan saat upload.');
        });
        dzEditInitialized = true;
    }

    // =============================================
    // CREATE
    // =============================================
    window.openCollectionCreateModal = function () {
        if (els.formCreate) els.formCreate.reset();
        showModal('create-collection');
        // Init Dropzone SETELAH modal visible agar dimensi terhitung
        requestAnimationFrame(function () {
            initDzCreate();
            if (dzCreate) { try { dzCreate.removeAllFiles(true); } catch (e) {} }
        });
    };

    var submitCreateBtn = document.getElementById('submit-create-collection-btn');
    if (submitCreateBtn) {
        submitCreateBtn.addEventListener('click', function () {
            var valid = true;
            document.querySelectorAll('[id^="error-create-col-"]').forEach(function (el) { el.classList.add('hidden'); });
            if (!document.getElementById('create-col-name').value.trim()) { document.getElementById('error-create-col-name').classList.remove('hidden'); valid = false; }
            if (!document.getElementById('create-col-category').value.trim()) { document.getElementById('error-create-col-category').classList.remove('hidden'); valid = false; }
            if (dzCreate && dzCreate.getQueuedFiles().length === 0) { document.getElementById('error-create-col-photo').classList.remove('hidden'); valid = false; }
            if (!valid) { showToast('warning', 'Validasi Gagal', 'Harap lengkapi kolom yang wajib.'); return; }
            if (dzCreate && dzCreate.getQueuedFiles().length > 0) { dzCreate.processQueue(); }
        });
    }

    // =============================================
    // EDIT
    // =============================================
    window.openCollectionEditModal = function (col) {
        document.getElementById('edit-col-name').value = col.name || '';
        document.getElementById('edit-col-name-en').value = col.name_en || '';
        document.getElementById('edit-col-scientific').value = col.scientific_name || '';
        document.getElementById('edit-col-category').value = col.category || '';
        document.getElementById('edit-col-category-en').value = col.category_en || '';
        els.formEdit.action = CFG.collectionsBaseUrl + '/' + col.id;

        // Render existing photo
        var photoWrap = document.getElementById('edit-col-existing-photo');
        if (col.image_path) {
            var imgSrc = col.image_path.startsWith('http') ? col.image_path : CFG.storageUrl + '/' + col.image_path;
            photoWrap.innerHTML = '<div class="relative group" data-photo-id="' + col.id + '">' +
                '<img src="' + imgSrc + '" class="w-20 h-16 rounded border border-gray-700 object-cover">' +
                '<button type="button" title="Hapus foto" class="btn-delete-col-photo absolute -top-1.5 -right-1.5 w-5 h-5 flex items-center justify-center rounded-full bg-[#E62C37] text-white text-xs font-bold shadow hover:bg-red-700 transition-colors">&times;</button></div>';
        } else {
            photoWrap.innerHTML = '<p class="text-xs text-gray-500 py-2">Belum ada foto.</p>';
        }

        showModal('edit-collection');
        // Init Dropzone SETELAH modal visible
        requestAnimationFrame(function () {
            initDzEdit();
            if (dzEdit) { try { dzEdit.removeAllFiles(true); } catch (e) {} }
        });
    };

    var submitEditBtn = document.getElementById('submit-edit-collection-btn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', function () {
            var valid = true;
            document.querySelectorAll('[id^="error-edit-col-"]').forEach(function (el) { el.classList.add('hidden'); });
            if (!document.getElementById('edit-col-name').value.trim()) { document.getElementById('error-edit-col-name').classList.remove('hidden'); valid = false; }
            if (!document.getElementById('edit-col-category').value.trim()) { document.getElementById('error-edit-col-category').classList.remove('hidden'); valid = false; }
            if (!valid) { showToast('warning', 'Validasi Gagal', 'Harap lengkapi kolom yang wajib.'); return; }
            if (dzEdit && dzEdit.getQueuedFiles().length > 0) {
                dzEdit.options.url = els.formEdit.action;
                dzEdit.processQueue();
            } else {
                fetch(els.formEdit.action, { method: 'POST', body: new FormData(els.formEdit), headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' } })
                    .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.json(); })
                    .then(function () { showToast('success', 'Berhasil!', 'Data koleksi diperbarui.'); setTimeout(function () { location.reload(); }, 1000); })
                    .catch(function (e) { console.error(e); showToast('error', 'Gagal!', 'Terjadi kesalahan.'); });
            }
        });
    }

    // Delete photo from edit modal
    document.addEventListener('click', function (e) {
        var btn = e.target.closest('.btn-delete-col-photo');
        if (!btn) return;
        pendingDeletePhotoEl = btn.closest('[data-photo-id]');
        showModal('confirm-delete-col-photo');
    });

    var confirmDelPhotoBtn = document.getElementById('confirm-delete-col-photo-btn');
    if (confirmDelPhotoBtn) {
        confirmDelPhotoBtn.addEventListener('click', function () {
            if (!pendingDeletePhotoEl) return;
            closeModal('confirm-delete-col-photo');
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'remove_image';
            input.value = '1';
            els.formEdit.appendChild(input);
            pendingDeletePhotoEl.remove();
            var photoWrap = document.getElementById('edit-col-existing-photo');
            photoWrap.innerHTML = '<p class="text-xs text-gray-500 py-2">Foto akan dihapus saat menyimpan.</p>';
            showToast('success', 'Siap', 'Foto akan dihapus saat Anda menyimpan perubahan.');
            pendingDeletePhotoEl = null;
        });
    }

    // =============================================
    // DELETE
    // =============================================
    window.openCollectionDeleteModal = function (id, name) {
        document.getElementById('delete-collection-name').innerText = '"' + name + '"';
        els.formDelete.action = CFG.collectionsBaseUrl + '/' + id;
        showModal('delete-collection');
    };

    // =============================================
    // ZOOM IMAGE
    // =============================================
    window.zoomImage = function (src) {
        var overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] bg-gray-950/90 flex items-center justify-center p-4 cursor-zoom-out';
        overlay.innerHTML = '<button class="absolute top-5 right-5 w-11 h-11 flex items-center justify-center bg-[#2d3748] hover:bg-[#E62C37] text-white rounded shadow-lg font-bold text-2xl z-[101]">&times;</button><img src="' + src + '" class="max-w-full max-h-[85vh] shadow-2xl rounded border border-gray-800">';
        document.body.appendChild(overlay);
        overlay.onclick = function () { overlay.remove(); };
        document.addEventListener('keydown', function esc(e) { if (e.key === 'Escape') { overlay.remove(); document.removeEventListener('keydown', esc); } });
    };

    // Session flash ditangani oleh admin.blade.php, tidak perlu duplikat di sini
})();
