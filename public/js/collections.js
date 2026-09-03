(function () {
    'use strict';

    var CFG = window.CollectionsConfig || {};
    var CLOUD_NAME = 'kjcs8wz3';
    var UPLOAD_PRESET = '4putra_unsigned';

    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }

    function uploadToCloudinary(file, folder, dzInstance) {
        var url = 'https://api.cloudinary.com/v1_1/' + CLOUD_NAME + '/upload';
        var fd = new FormData();
        fd.append('file', file);
        fd.append('upload_preset', UPLOAD_PRESET);
        if (folder) fd.append('folder', folder);

        return new Promise(function (resolve, reject) {
            var xhr = new XMLHttpRequest();
            xhr.open('POST', url);

            xhr.upload.onprogress = function (e) {
                if (e.lengthComputable && dzInstance) {
                    var pct = Math.round((e.loaded / e.total) * 100);
                    dzInstance.emit('uploadprogress', file, pct, e.loaded);
                }
            };

            xhr.onload = function () {
                if (xhr.status >= 200 && xhr.status < 300) {
                    var data = JSON.parse(xhr.responseText);
                    if (data.secure_url) {
                        if (dzInstance) {
                            dzInstance.emit('success', file);
                            dzInstance.emit('complete', file);
                        }
                        resolve(data.secure_url);
                    } else {
                        reject(new Error('Cloudinary tidak mengembalikan URL'));
                    }
                } else {
                    reject(new Error('Upload Cloudinary gagal: HTTP ' + xhr.status));
                }
            };

            xhr.onerror = function () {
                reject(new Error('Network error saat upload ke Cloudinary'));
            };

            file.status = 'uploading';
            if (dzInstance) dzInstance.emit('processing', file);
            xhr.send(fd);
        });
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

    function initDzCreate() {
        if (dzCreateInitialized) return;
        var Dropzone = window.Dropzone;
        if (!Dropzone) { console.warn('[INFO] Dropzone tidak ter-load.'); return; }
        Dropzone.autoDiscover = false;

        dzCreate = new Dropzone('#dz-collection-create', {
            url: '/',
            autoProcessQueue: false,
            uploadMultiple: false,
            paramName: 'image',
            maxFilesize: 10,
            acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
            addRemoveLinks: true,
            dictDefaultMessage: 'Tarik file foto ke sini atau klik untuk memilih',
            dictRemoveFile: 'Hapus',
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
            maxFilesize: 10,
            acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
            addRemoveLinks: true,
            dictDefaultMessage: 'Tarik file foto baru ke sini',
            dictRemoveFile: 'Hapus',
        });
        dzEditInitialized = true;
    }

    // =============================================
    // CREATE
    // =============================================
    window.openCollectionCreateModal = function () {
        if (els.formCreate) els.formCreate.reset();
        var parentInfo = document.getElementById('create-col-parent-info');
        if (parentInfo) parentInfo.classList.add('hidden');
        showModal('create-collection');
        requestAnimationFrame(function () {
            initDzCreate();
            if (dzCreate) { try { dzCreate.removeAllFiles(true); } catch (e) {} }
        });
    };

    // Info "Menambahkan varian untuk: ..." hilang saat dropdown induk dikosongkan manual
    var parentSelectCreate = document.getElementById('create-col-parent');
    if (parentSelectCreate) {
        parentSelectCreate.addEventListener('change', function () {
            var info = document.getElementById('create-col-parent-info');
            if (info && !parentSelectCreate.value) info.classList.add('hidden');
        });
    }

    window.openCreateVariantModal = function (col) {
        window.openCollectionCreateModal();
        var select = document.getElementById('create-col-parent');
        var info = document.getElementById('create-col-parent-info');
        var cat = document.getElementById('create-col-category');
        if (select) select.value = String(col.id);
        if (cat && col.category) cat.value = col.category;
        if (info) {
            info.textContent = 'Menambahkan varian untuk: ' + (col.name || '');
            info.classList.remove('hidden');
        }
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

            var btn = submitCreateBtn;
            btn.disabled = true;
            btn.textContent = 'Mengupload...';

            var files = dzCreate.getQueuedFiles();
            var folder = '4putra/collections';

            // Upload ke Cloudinary dulu
            var uploads = files.map(function (f) { return uploadToCloudinary(f, folder, dzCreate); });
            Promise.all(uploads)
                .then(function (urls) {
                    // Kirim data + URL ke Laravel
                    var fd = new FormData(els.formCreate);
                    urls.forEach(function (url, i) { fd.append('cloudinary_urls[]', url); });
                    // Hapus file dari FormData (karena kita kirim URL)
                    fd.delete('image');

                    return fetch(CFG.storeUrl, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || CFG.csrfToken },
                    });
                })
                .then(function (r) {
                    if (r.status === 419) {
                        showToast('error', 'Sesi Berakhir', 'Sesi Anda telah berakhir. Halaman akan dimuat ulang...');
                        setTimeout(function () { location.reload(); }, 2000);
                        throw new Error('CSRF token expired');
                    }
                    if (!r.ok) return r.json().then(function (e) { throw new Error(e.message || 'Server error'); });
                    return r.json();
                })
                .then(function () {
                    showToast('success', 'Berhasil!', 'Koleksi baru telah ditambahkan.');
                    refreshAdminList();
                })
                .catch(function (e) {
                    console.error('Create error:', e);
                    if (e.message !== 'CSRF token expired') {
                        showToast('error', 'Gagal!', e.message || 'Terjadi kesalahan saat upload.');
                    }
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Simpan';
                });
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
        document.getElementById('edit-col-sort-order').value = col.sort_order ?? 0;
        els.formEdit.action = CFG.collectionsBaseUrl + '/' + col.id;

        var parentSelect = document.getElementById('edit-col-parent');
        if (parentSelect) {
            parentSelect.value = col.parent_id ? String(col.parent_id) : '';
            // Induk tidak boleh dirinya sendiri
            Array.prototype.forEach.call(parentSelect.options, function (opt) {
                opt.disabled = opt.value === String(col.id);
            });
        }

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

            var btn = submitEditBtn;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            var files = dzEdit ? dzEdit.getQueuedFiles() : [];
            var folder = '4putra/collections';

            var uploadPromise = files.length > 0
                ? Promise.all(files.map(function (f) { return uploadToCloudinary(f, folder, dzEdit); }))
                : Promise.resolve([]);

            uploadPromise
                .then(function (urls) {
                    var fd = new FormData(els.formEdit);
                    urls.forEach(function (url) { fd.append('cloudinary_urls[]', url); });
                    fd.delete('image');

                    return fetch(els.formEdit.action, {
                        method: 'POST',
                        body: fd,
                        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || CFG.csrfToken },
                    });
                })
                .then(function (r) {
                    if (r.status === 419) {
                        showToast('error', 'Sesi Berakhir', 'Sesi Anda telah berakhir. Halaman akan dimuat ulang...');
                        setTimeout(function () { location.reload(); }, 2000);
                        throw new Error('CSRF token expired');
                    }
                    if (!r.ok) return r.json().then(function (e) { throw new Error(e.message || 'Server error'); });
                    return r.json();
                })
                .then(function () {
                    showToast('success', 'Berhasil!', 'Data koleksi diperbarui.');
                    refreshAdminList();
                })
                .catch(function (e) {
                    console.error('Edit error:', e);
                    if (e.message !== 'CSRF token expired') {
                        showToast('error', 'Gagal!', e.message || 'Terjadi kesalahan.');
                    }
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Perbarui';
                });
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
})();
