/**
 * facilities.js
 * Logic untuk halaman Monitoring Data Fasilitas (Admin Dashboard).
 * File ini MURNI JavaScript -- tidak ada syntax Blade di sini.
 * Data dinamis dari Laravel diambil dari window.FacilitiesConfig.
 *
 * Upload gambar langsung dari browser ke Cloudinary (client-side),
 * bypass server Vercel untuk file upload.
 */

(function () {
    'use strict';

    var CFG = window.FacilitiesConfig || {};
    var CLOUD_NAME = 'kjcs8wz3';
    var UPLOAD_PRESET = '4putra_unsigned';
    var UPLOAD_FOLDER = '4putra/facilities';

    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }

    function csrf() {
        return document.querySelector('meta[name="csrf-token"]')?.content || CFG.csrfToken;
    }

    function uploadToCloudinary(file, dzInstance) {
        var url = 'https://api.cloudinary.com/v1_1/' + CLOUD_NAME + '/upload';
        var fd = new FormData();
        fd.append('file', file);
        fd.append('upload_preset', UPLOAD_PRESET);
        fd.append('folder', UPLOAD_FOLDER);

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
        formCreate: document.getElementById('form-create-facilities'),
        formEdit: document.getElementById('form-edit-action'),
        formDelete: document.getElementById('form-delete-action'),
    };

    var dzCreate = null;
    var dzEdit = null;

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

    function openCreateModal() {
        populateVideoList('create-video-url-list', ['']);
        showModal('create');
        requestAnimationFrame(function () {
            initDzCreate();
            if (dzCreate) { try { dzCreate.removeAllFiles(true); } catch (e) {} }
        });
    }

    function openDeleteModal(id, title) {
        document.getElementById('delete-target-name').innerText = '"' + title + '"';
        els.formDelete.action = CFG.facilitiesBaseUrl + '/' + id;
        showModal('delete');
    }

    function openEditModal(facility) {
        document.getElementById('edit-title').value = facility.title || '';
        document.getElementById('edit-title-en').value = facility.title_en || '';
        document.getElementById('edit-category').value = facility.category || '';
        document.getElementById('edit-category-en').value = facility.category_en || '';
        document.getElementById('edit-description').value = facility.description || '';
        document.getElementById('edit-description-en').value = facility.description_en || '';
        populateVideoList('edit-video-url-list', facility.video_urls || []);
        els.formEdit.action = CFG.facilitiesBaseUrl + '/' + facility.id;

        renderExistingPhotos(facility.images || []);
        // Bersihkan penanda hapus foto dari sesi edit sebelumnya
        els.formEdit.querySelectorAll('input[name="removed_images[]"]').forEach(function (el) { el.remove(); });

        showModal('edit');
        requestAnimationFrame(function () {
            initDzEdit();
            if (dzEdit) { try { dzEdit.removeAllFiles(true); } catch (e) {} }
        });
    }

    function renderExistingPhotos(images) {
        var wrap = document.getElementById('edit-existing-photos');
        wrap.innerHTML = '';

        if (!images.length) {
            wrap.innerHTML = '<p class="text-xs text-gray-500 py-2">Belum ada foto tersimpan untuk fasilitas ini.</p>';
            return;
        }

        var frag = document.createDocumentFragment();
        images.forEach(function (imgUrl) {
            var thumb = document.createElement('div');
            thumb.className = 'relative group';
            thumb.dataset.imageUrl = imgUrl;
            var imgSrc = imgUrl.startsWith('http')
                ? imgUrl.replace('/upload/', '/upload/w_150,h_150,c_fill,q_auto,f_auto/')
                : CFG.storageUrl + '/' + imgUrl;
            thumb.innerHTML =
                '<img src="' + imgSrc + '" ' +
                'class="w-16 h-16 rounded border border-gray-700 object-cover cursor-pointer" ' +
                'loading="lazy" alt="Foto tersimpan">' +
                '<button type="button" title="Hapus foto ini" ' +
                'class="btn-delete-existing-photo absolute -top-1.5 -right-1.5 w-5 h-5 flex items-center justify-center rounded-full bg-[#E62C37] text-white text-xs font-bold shadow hover:bg-red-700 transition-colors">' +
                '&times;</button>';
            frag.appendChild(thumb);
        });
        wrap.appendChild(frag);
    }

    var pendingDeleteThumb = null;

    var existingPhotosWrap = document.getElementById('edit-existing-photos');
    if (existingPhotosWrap) {
        existingPhotosWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-existing-photo');
            if (!btn) return;
            pendingDeleteThumb = btn.closest('[data-image-url]');
            showModal('confirm-delete-photo');
        });
    }

    var confirmDeletePhotoBtn = document.getElementById('confirm-delete-photo-btn');
    if (confirmDeletePhotoBtn) {
        confirmDeletePhotoBtn.addEventListener('click', function () {
            closeModal('confirm-delete-photo');
            if (!pendingDeleteThumb) return;
            var thumb = pendingDeleteThumb;
            pendingDeleteThumb = null;

            // Tandai URL untuk difilter controller saat update (JSON images array)
            var input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'removed_images[]';
            input.value = thumb.dataset.imageUrl;
            els.formEdit.appendChild(input);

            thumb.style.opacity = '0.3';
            thumb.querySelector('.btn-delete-existing-photo').remove();
            showToast('success', 'Siap', 'Foto akan dihapus saat Anda menyimpan perubahan.');
        });
    }

    function zoomImage(src) {
        var overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] bg-gray-950/90 flex items-center justify-center p-4 cursor-zoom-out';
        overlay.innerHTML =
            '<button type="button" id="zoom-close-btn" ' +
            'class="absolute top-5 right-5 w-11 h-11 flex items-center justify-center bg-[#2d3748] hover:bg-[#E62C37] text-white rounded shadow-lg font-bold text-2xl z-[101]">&times;</button>' +
            '<img src="' + src + '" id="zoomed-img" class="max-w-full max-h-[85vh] shadow-2xl rounded border border-gray-800 opacity-0 transition-opacity duration-300">';
        document.body.appendChild(overlay);
        var img = overlay.querySelector('#zoomed-img');
        img.addEventListener('error', function () { img.style.border = '2px solid #E62C37'; });
        requestAnimationFrame(function () { img.style.opacity = '1'; });

        function closeZoom() {
            overlay.remove();
            document.removeEventListener('keydown', escHandler);
        }
        function escHandler(e) { if (e.key === 'Escape') closeZoom(); }
        overlay.addEventListener('click', closeZoom);
        overlay.querySelector('#zoom-close-btn').addEventListener('click', function (e) { e.stopPropagation(); closeZoom(); });
        document.addEventListener('keydown', escHandler);
    }

    function submitWithUploads(formEl, files, doneBtnText, successTitle, successMsg) {
        var btn = formEl.id === 'form-create-facilities' ? submitCreateBtn : submitEditBtn;
        if (!btn) return Promise.reject(new Error('Tombol submit tidak ditemukan'));

        btn.disabled = true;
        btn.textContent = 'Mengupload...';

        var uploadPromise = files.length > 0
            ? Promise.all(files.map(function (f) { return uploadToCloudinary(f, formEl.id === 'form-create-facilities' ? dzCreate : dzEdit); }))
            : Promise.resolve([]);

        return uploadPromise
            .then(function (urls) {
                var fd = new FormData(formEl);
                urls.forEach(function (url) {
                    fd.append('cloudinary_urls[]', url);
                });
                fd.delete('images');

                return fetch(formEl.action, {
                    method: 'POST',
                    body: fd,
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf() },
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
                showToast('success', successTitle, successMsg);
                refreshAdminList();
            })
            .catch(function (e) {
                console.error('Submit error:', e);
                if (e.message !== 'CSRF token expired') {
                    showToast('error', 'Gagal!', e.message || 'Terjadi kesalahan saat menyimpan data.');
                }
            })
            .finally(function () {
                btn.disabled = false;
                btn.textContent = doneBtnText;
            });
    }

    // =============================================
    // VALIDASI CREATE
    // =============================================
    var submitCreateBtn = document.getElementById('submit-create-btn');
    if (submitCreateBtn) {
        submitCreateBtn.addEventListener('click', function () {
            var isValid = true;
            var title = document.getElementById('create-input-title').value.trim();
            var category = document.getElementById('create-input-category').value.trim();
            var desc = document.getElementById('create-input-description').value.trim();

            document.querySelectorAll('[id^="error-create-"]').forEach(function (el) { el.classList.add('hidden'); });

            if (!title) { document.getElementById('error-create-title').classList.remove('hidden'); isValid = false; }
            if (!category) { document.getElementById('error-create-category').classList.remove('hidden'); isValid = false; }
            if (!desc) { document.getElementById('error-create-description').classList.remove('hidden'); isValid = false; }
            if (dzCreate && dzCreate.getQueuedFiles().length === 0) {
                document.getElementById('error-create-photos').classList.remove('hidden');
                isValid = false;
            }

            if (!isValid) {
                showToast('warning', 'Validasi Gagal', 'Harap lengkapi semua kolom yang wajib diisi.');
                return;
            }

            submitWithUploads(
                els.formCreate,
                dzCreate.getQueuedFiles(),
                'Simpan Data',
                'Berhasil!',
                'Fasilitas baru telah ditambahkan.'
            );
        });
    }

    // =============================================
    // EDIT: validasi -> KONFIRMASI -> submit
    // =============================================
    var submitEditBtn = document.getElementById('submit-edit-btn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', function () {
            var isValid = true;
            var title = document.getElementById('edit-title').value.trim();
            var category = document.getElementById('edit-category').value.trim();
            var desc = document.getElementById('edit-description').value.trim();

            document.querySelectorAll('[id^="error-edit-"]').forEach(function (el) { el.classList.add('hidden'); });

            if (!title) { document.getElementById('error-edit-title').classList.remove('hidden'); isValid = false; }
            if (!category) { document.getElementById('error-edit-category').classList.remove('hidden'); isValid = false; }
            if (!desc) { document.getElementById('error-edit-description').classList.remove('hidden'); isValid = false; }

            if (!isValid) {
                showToast('warning', 'Validasi Gagal', 'Harap lengkapi semua kolom yang wajib diisi.');
                return;
            }

            showModal('confirm-update');
        });
    }

    var confirmUpdateBtn = document.getElementById('confirm-update-proceed-btn');
    if (confirmUpdateBtn && submitEditBtn) {
        confirmUpdateBtn.addEventListener('click', function () {
            closeModal('confirm-update');

            var files = dzEdit ? dzEdit.getQueuedFiles() : [];
            submitWithUploads(
                els.formEdit,
                files,
                'Perbarui Data',
                'Berhasil Diubah!',
                'Fasilitas telah diperbarui.'
            );
        });
    }

    // =============================================
    // DROPZONE — lazy init
    // =============================================
    var dzCreateInitialized = false;
    var dzEditInitialized = false;

    function initDzCreate() {
        if (dzCreateInitialized) return;
        try {
            var Dropzone = window.Dropzone;
            if (!Dropzone || !els.formCreate) { console.warn('[INFO] Dropzone tidak ter-load.'); return; }
            Dropzone.autoDiscover = false;

            dzCreate = new Dropzone('#dropzone-create', {
                url: '/',
                autoProcessQueue: false,
                uploadMultiple: true,
                parallelUploads: 10,
                paramName: 'images',
                maxFilesize: 20,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto ke sini atau klik untuk memilih',
                dictRemoveFile: 'Hapus',
            });
            dzCreateInitialized = true;
        } catch (err) {
            console.error('[DROPZONE CREATE INIT ERROR]', err);
        }
    }

    function initDzEdit() {
        if (dzEditInitialized) return;
        try {
            var Dropzone = window.Dropzone;
            if (!Dropzone) return;
            Dropzone.autoDiscover = false;

            dzEdit = new Dropzone('#dropzone-edit', {
                url: '/',
                autoProcessQueue: false,
                uploadMultiple: true,
                parallelUploads: 10,
                paramName: 'images',
                maxFilesize: 20,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,image/webp',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto baru ke sini',
                dictRemoveFile: 'Hapus',
            });
            dzEditInitialized = true;
        } catch (err) {
            console.error('[DROPZONE EDIT INIT ERROR]', err);
        }
    }

    // =============================================
    // MULTIPLE VIDEO LINKS UTILITY
    // =============================================
    function videoRowHtml(value) {
        return '<input type="url" name="video_urls[]" value="' + (value || '') + '" ' +
            'placeholder="https://youtube.com/watch?v=..." ' +
            'class="flex-1 p-2.5 text-sm bg-white dark:bg-[#151a22] border border-gray-300 dark:border-gray-700 rounded-xl text-gray-900 dark:text-white focus:outline-none focus:border-[#E62C37]">' +
            '<button type="button" onclick="hapusVideoRow(this)" class="px-2.5 py-2.5 text-sm font-bold bg-red-600/20 text-red-400 border border-red-500/30 rounded-xl hover:bg-red-600/30 transition-colors">&times;</button>';
    }

    function tambahVideoRow(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var wrapper = document.createElement('div');
        wrapper.className = 'flex items-center gap-2';
        wrapper.innerHTML = videoRowHtml('');
        container.appendChild(wrapper);
    }

    function hapusVideoRow(btn) {
        btn.closest('.flex').remove();
    }

    function populateVideoList(containerId, urls) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        var arr = Array.isArray(urls) && urls.length ? urls : [''];
        arr.forEach(function (url) {
            var wrapper = document.createElement('div');
            wrapper.className = 'flex items-center gap-2';
            wrapper.innerHTML = videoRowHtml(url);
            container.appendChild(wrapper);
        });
    }

    window.tambahVideoRow = tambahVideoRow;
    window.hapusVideoRow = hapusVideoRow;

    // =============================================
    // EXPOSE FUNGSI
    // =============================================
    window.openCreateModal = openCreateModal;
    window.openEditModal = openEditModal;
    window.openDeleteModal = openDeleteModal;
    window.closeModal = closeModal;
    window.zoomImage = zoomImage;
})();
