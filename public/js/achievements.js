/**
 * achievements.js
 * Logic untuk halaman Monitoring Data Achievements (Admin Dashboard).
 * File ini MURNI JavaScript -- tidak ada syntax Blade di sini.
 * Data dinamis dari Laravel diambil dari window.AchievementsConfig.
 *
 * Upload gambar/video langsung dari browser ke Cloudinary (client-side),
 * bypass server Vercel untuk file upload.
 */

(function () {
    'use strict';

    var CFG = window.AchievementsConfig || {};
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
                        resolve({ url: data.secure_url, resource_type: data.resource_type || 'image' });
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
        formCreate: document.getElementById('form-create-achievements'),
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
        showModal('create');
        requestAnimationFrame(function () {
            initDzCreate();
            if (dzCreate) { try { dzCreate.removeAllFiles(true); } catch (e) {} }
        });
    }

    function openDeleteModal(id, title) {
        document.getElementById('delete-target-name').innerText = '"' + title + '"';
        els.formDelete.action = CFG.achievementsBaseUrl + '/' + id;
        showModal('delete');
    }

    function openEditModal(achievement) {
        document.getElementById('edit-title').value = achievement.title;
        document.getElementById('edit-title-en').value = achievement.title_en || '';
        document.getElementById('edit-title-highlight').value = achievement.title_highlight || '';
        document.getElementById('edit-title-highlight-en').value = achievement.title_highlight_en || '';
        document.getElementById('edit-year').value = achievement.year;
        document.getElementById('edit-date').value = achievement.date;
        document.getElementById('edit-date-end').value = achievement.date_end || '';
        document.getElementById('edit-location').value = achievement.location || '';
        document.getElementById('edit-description').value = achievement.description;
        document.getElementById('edit-description-en').value = achievement.description_en || '';
        els.formEdit.action = CFG.achievementsBaseUrl + '/' + achievement.id;

        populateLinkList('edit-video-url-list', achievement.video_url);
        populateLinkList('edit-external-link-list', achievement.external_link);

        var videoWrap = document.getElementById('edit-existing-video-wrap');
        var videoName = document.getElementById('edit-video-name');
        if (videoWrap && videoName) {
            if (achievement.video_file) {
                videoName.textContent = achievement.video_file;
                videoWrap.classList.remove('hidden');
            } else {
                videoWrap.classList.add('hidden');
            }
        }

        renderExistingPhotos(achievement.images || []);
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
            wrap.innerHTML = '<p class="text-xs text-gray-500 py-2">Belum ada foto tersimpan untuk portofolio ini.</p>';
            return;
        }

        var frag = document.createDocumentFragment();
        images.forEach(function (img) {
            var thumb = document.createElement('div');
            thumb.className = 'relative group';
            thumb.dataset.imageId = img.id;
            var imgSrc = img.image_path.startsWith('http') ? img.image_path : CFG.storageUrl + '/' + img.image_path;
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
    var pendingDeleteImageId = null;

    var existingPhotosWrap = document.getElementById('edit-existing-photos');
    if (existingPhotosWrap) {
        existingPhotosWrap.addEventListener('click', function (e) {
            var btn = e.target.closest('.btn-delete-existing-photo');
            if (!btn) return;
            var thumb = btn.closest('[data-image-id]');
            pendingDeleteThumb = thumb;
            pendingDeleteImageId = thumb.dataset.imageId;
            showModal('confirm-delete-photo');
        });
    }

    var confirmDeletePhotoBtn = document.getElementById('confirm-delete-photo-btn');
    if (confirmDeletePhotoBtn) {
        confirmDeletePhotoBtn.addEventListener('click', function () {
            if (!pendingDeleteThumb || !pendingDeleteImageId) return;
            closeModal('confirm-delete-photo');
            var thumb = pendingDeleteThumb;
            var imageId = pendingDeleteImageId;
            var btn = thumb.querySelector('.btn-delete-existing-photo');
            pendingDeleteThumb = null;
            pendingDeleteImageId = null;
            btn.disabled = true;
            thumb.style.opacity = '0.4';

            fetch(CFG.imagesDeleteBaseUrl + '/' + imageId, {
                method: 'DELETE',
                headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || CFG.csrfToken, 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
            })
                .then(function (res) {
                    if (!res.ok) return res.json().then(function (e) { throw new Error(e.message || 'HTTP ' + res.status); });
                    return res.json();
                })
                .then(function () { thumb.remove(); showToast('success', 'Terhapus', 'Foto berhasil dihapus dari galeri.'); })
                .catch(function (err) {
                    console.error('Delete photo error:', err);
                    thumb.style.opacity = '1'; btn.disabled = false;
                    showToast('error', 'Gagal', err.message || 'Foto gagal dihapus, coba lagi.');
                });
        });
    }

    var btnDeleteVideo = document.getElementById('btn-delete-video');
    if (btnDeleteVideo) {
        btnDeleteVideo.addEventListener('click', function () { showModal('confirm-delete-video'); });
    }

    var confirmDeleteVideoBtn = document.getElementById('confirm-delete-video-btn');
    if (confirmDeleteVideoBtn) {
        confirmDeleteVideoBtn.addEventListener('click', function () {
            closeModal('confirm-delete-video');
            var existing = els.formEdit.querySelector('input[name="remove_video"]');
            if (!existing) {
                var input = document.createElement('input');
                input.type = 'hidden'; input.name = 'remove_video'; input.value = '1';
                els.formEdit.appendChild(input);
            }
            var videoWrap = document.getElementById('edit-existing-video-wrap');
            if (videoWrap) videoWrap.classList.add('hidden');
            showToast('success', 'Siap', 'Video akan dihapus saat Anda menyimpan perubahan.');
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

    // =============================================
    // VALIDASI CREATE
    // =============================================
    var submitCreateBtn = document.getElementById('submit-create-btn');
    if (submitCreateBtn) {
        submitCreateBtn.addEventListener('click', function () {
            var isValid = true;
            var title = document.getElementById('create-input-title').value.trim();
            var year = document.getElementById('create-input-year').value.trim();
            var date = document.getElementById('create-input-date').value.trim();
            var desc = document.getElementById('create-input-description').value.trim();

            document.querySelectorAll('[id^="error-create-"]').forEach(function (el) { el.classList.add('hidden'); });

            if (!title) { document.getElementById('error-create-title').classList.remove('hidden'); isValid = false; }
            if (!year) { document.getElementById('error-create-year').classList.remove('hidden'); isValid = false; }
            if (!date) { document.getElementById('error-create-date').classList.remove('hidden'); isValid = false; }
            if (!desc) { document.getElementById('error-create-description').classList.remove('hidden'); isValid = false; }
            if (dzCreate && dzCreate.getQueuedFiles().length === 0) {
                document.getElementById('error-create-photos').classList.remove('hidden');
                isValid = false;
            }

            if (!isValid) {
                showToast('warning', 'Validasi Gagal', 'Harap lengkapi semua kolom yang wajib diisi.');
                return;
            }

            var btn = submitCreateBtn;
            btn.disabled = true;
            btn.textContent = 'Mengupload...';

            var files = dzCreate.getQueuedFiles();
            var folder = '4putra/achievements';

            var uploads = files.map(function (f) { return uploadToCloudinary(f, folder, dzCreate); });
            Promise.all(uploads)
                .then(function (results) {
                    var fd = new FormData(els.formCreate);
                    results.forEach(function (r) {
                        fd.append('cloudinary_urls[]', r.url);
                        fd.append('cloudinary_types[]', r.resource_type);
                    });
                    fd.delete('images');

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
                    showToast('success', 'Berhasil!', 'Portofolio baru aviary telah ditambahkan.');
                    refreshAdminList();
                })
                .catch(function (e) {
                    console.error('Create error:', e);
                    if (e.message !== 'CSRF token expired') {
                        showToast('error', 'Gagal!', e.message || 'Terjadi kesalahan saat upload foto.');
                    }
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Simpan Data';
                });
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
            var year = document.getElementById('edit-year').value.trim();
            var date = document.getElementById('edit-date').value.trim();
            var desc = document.getElementById('edit-description').value.trim();

            document.querySelectorAll('[id^="error-edit-"]').forEach(function (el) { el.classList.add('hidden'); });

            if (!title) { document.getElementById('error-edit-title').classList.remove('hidden'); isValid = false; }
            if (!year) { document.getElementById('error-edit-year').classList.remove('hidden'); isValid = false; }
            if (!date) { document.getElementById('error-edit-date').classList.remove('hidden'); isValid = false; }
            if (!desc) { document.getElementById('error-edit-description').classList.remove('hidden'); isValid = false; }

            if (!isValid) {
                showToast('warning', 'Validasi Gagal', 'Harap lengkapi semua kolom yang wajib diisi.');
                return;
            }

            showModal('confirm-update');
        });
    }

    var confirmUpdateBtn = document.getElementById('confirm-update-proceed-btn');
    if (confirmUpdateBtn) {
        confirmUpdateBtn.addEventListener('click', function () {
            closeModal('confirm-update');

            var btn = submitEditBtn;
            btn.disabled = true;
            btn.textContent = 'Menyimpan...';

            var files = dzEdit ? dzEdit.getQueuedFiles() : [];
            var folder = '4putra/achievements';

            var uploadPromise = files.length > 0
                ? Promise.all(files.map(function (f) { return uploadToCloudinary(f, folder, dzEdit); }))
                : Promise.resolve([]);

            uploadPromise
                .then(function (results) {
                    var fd = new FormData(els.formEdit);
                    results.forEach(function (r) {
                        fd.append('cloudinary_urls[]', r.url);
                        fd.append('cloudinary_types[]', r.resource_type);
                    });
                    fd.delete('images');

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
                    showToast('success', 'Berhasil Diubah!', 'Data pencapaian telah diperbarui.');
                    refreshAdminList();
                })
                .catch(function (e) {
                    console.error('Update error:', e);
                    if (e.message !== 'CSRF token expired') {
                        showToast('error', 'Gagal!', e.message || 'Terjadi kesalahan saat memperbarui data.');
                    }
                })
                .finally(function () {
                    btn.disabled = false;
                    btn.textContent = 'Perbarui Data';
                });
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
                maxFilesize: 50,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mov,video/webm,video/avi',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto/video ke sini atau klik untuk memilih',
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
                maxFilesize: 50,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mov,video/webm,video/avi',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto/video baru ke sini',
                dictRemoveFile: 'Hapus',
            });
            dzEditInitialized = true;
        } catch (err) {
            console.error('[DROPZONE EDIT INIT ERROR]', err);
        }
    }

    // =============================================
    // MULTIPLE LINKS UTILITY
    // =============================================
    function tambahLinkItem(containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;
        var wrapper = document.createElement('div');
        wrapper.className = 'flex items-center gap-2';
        var isVideo = containerId.indexOf('video') !== -1;
        wrapper.innerHTML =
            '<input type="url" name="' + (isVideo ? 'video_url[]' : 'external_link[]') + '" ' +
            'placeholder="' + (isVideo ? 'https://...' : 'https://berita.com/artikel...') + '" ' +
            'class="flex-1 p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">' +
            '<button type="button" onclick="hapusLinkItem(this)" class="px-2.5 py-2.5 text-sm font-bold bg-red-600/20 text-red-400 border border-red-500/30 rounded-xl hover:bg-red-600/30 transition-colors">&times;</button>';
        container.appendChild(wrapper);
    }

    function hapusLinkItem(btn) {
        btn.closest('.flex').remove();
    }

    function populateLinkList(containerId, links) {
        var container = document.getElementById(containerId);
        if (!container) return;
        container.innerHTML = '';
        var isVideo = containerId.indexOf('video') !== -1;
        var arr = [];
        if (typeof links === 'string' && links) {
            try { arr = JSON.parse(links); } catch (e) { arr = [links]; }
        } else if (Array.isArray(links)) {
            arr = links;
        }
        if (arr.length === 0) arr = [''];
        arr.forEach(function (url) {
            var wrapper = document.createElement('div');
            wrapper.className = 'flex items-center gap-2';
            wrapper.innerHTML =
                '<input type="url" name="' + (isVideo ? 'video_url[]' : 'external_link[]') + '" value="' + (url || '') + '" ' +
                'placeholder="' + (isVideo ? 'https://...' : 'https://berita.com/artikel...') + '" ' +
                'class="flex-1 p-2.5 text-sm bg-[#151a22] border border-gray-700 rounded-xl text-white focus:outline-none focus:border-[#E62C37]">' +
                '<button type="button" onclick="hapusLinkItem(this)" class="px-2.5 py-2.5 text-sm font-bold bg-red-600/20 text-red-400 border border-red-500/30 rounded-xl hover:bg-red-600/30 transition-colors">&times;</button>';
            container.appendChild(wrapper);
        });
    }

    window.tambahLinkItem = tambahLinkItem;
    window.hapusLinkItem = hapusLinkItem;

    // =============================================
    // EXPOSE FUNGSI
    // =============================================
    window.openCreateModal = openCreateModal;
    window.openEditModal = openEditModal;
    window.openDeleteModal = openDeleteModal;
    window.closeModal = closeModal;
    window.zoomImage = zoomImage;
})();
