/**
 * achievements.js
 * Logic untuk halaman Monitoring Data Achievements (Admin Dashboard).
 * File ini MURNI JavaScript -- tidak ada syntax Blade di sini.
 * Data dinamis dari Laravel diambil dari window.AchievementsConfig
 * (didefinisikan lewat inline script kecil di index.blade.php).
 *
 * Toast notification menggunakan shared toast.js (dimuat sebelum file ini).
 *
 * Taruh file ini di: public/js/achievements.js
 */

(function () {
    'use strict';

    const CFG = window.AchievementsConfig || {};
    function showToast(type, title, msg) {
        if (window.showToast) { window.showToast(type, title, msg); }
        else { console.warn('[TOAST]', type, title, msg); }
    }

    // =============================================
    // CACHE DOM REFERENCES SEKALI SAJA
    // =============================================
    const els = {
        formCreate: document.getElementById('form-create-achievements'),
        formEdit: document.getElementById('form-edit-action'),
        formDelete: document.getElementById('form-delete-action'),
    };

    let dzCreate = null;
    let dzEdit = null;

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

    function openCreateModal() {
        showModal('create');
        // Init Dropzone SETELAH modal visible
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

    // =============================================
    // EDIT MODAL: isi form + render galeri foto lama
    // =============================================
    function openEditModal(achievement) {
        document.getElementById('edit-title').value = achievement.title;
        document.getElementById('edit-title-en').value = achievement.title_en || '';
        document.getElementById('edit-title-highlight').value = achievement.title_highlight || '';
        document.getElementById('edit-title-highlight-en').value = achievement.title_highlight_en || '';
        document.getElementById('edit-year').value = achievement.year;
        document.getElementById('edit-date').value = achievement.date;
        document.getElementById('edit-description').value = achievement.description;
        document.getElementById('edit-description-en').value = achievement.description_en || '';
        els.formEdit.action = CFG.achievementsBaseUrl + '/' + achievement.id;

        // Populate video URLs (multi)
        populateLinkList('edit-video-url-list', achievement.video_url);

        // Populate external links (multi)
        populateLinkList('edit-external-link-list', achievement.external_link);

        // Tampilkan info video yang sudah ada
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
        // Init Dropzone SETELAH modal visible
        requestAnimationFrame(function () {
            initDzEdit();
            if (dzEdit) { try { dzEdit.removeAllFiles(true); } catch (e) {} }
        });
    }

    function renderExistingPhotos(images) {
        const wrap = document.getElementById('edit-existing-photos');
        wrap.innerHTML = '';

        if (!images.length) {
            wrap.innerHTML = '<p class="text-xs text-gray-500 py-2">Belum ada foto tersimpan untuk portofolio ini.</p>';
            return;
        }

        const frag = document.createDocumentFragment();
        images.forEach(function (img) {
            const thumb = document.createElement('div');
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

    // Event delegation: 1 listener untuk semua tombol hapus foto
    let pendingDeleteThumb = null;
    let pendingDeleteImageId = null;

    const existingPhotosWrap = document.getElementById('edit-existing-photos');
    if (existingPhotosWrap) {
        existingPhotosWrap.addEventListener('click', function (e) {
            const btn = e.target.closest('.btn-delete-existing-photo');
            if (!btn) return;

            const thumb = btn.closest('[data-image-id]');
            const imageId = thumb.dataset.imageId;

            // Simpan state dan tampilkan modal konfirmasi
            pendingDeleteThumb = thumb;
            pendingDeleteImageId = imageId;
            showModal('confirm-delete-photo');
        });
    }

    // Tombol konfirmasi hapus foto di modal
    const confirmDeletePhotoBtn = document.getElementById('confirm-delete-photo-btn');
    if (confirmDeletePhotoBtn) {
        confirmDeletePhotoBtn.addEventListener('click', function () {
            if (!pendingDeleteThumb || !pendingDeleteImageId) return;

            closeModal('confirm-delete-photo');
            const thumb = pendingDeleteThumb;
            const imageId = pendingDeleteImageId;
            const btn = thumb.querySelector('.btn-delete-existing-photo');

            pendingDeleteThumb = null;
            pendingDeleteImageId = null;

            btn.disabled = true;
            thumb.style.opacity = '0.4';

            fetch(CFG.imagesDeleteBaseUrl + '/' + imageId, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': CFG.csrfToken,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                }
            })
                .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                .then(function () {
                    thumb.remove();
                    showToast('success', 'Terhapus', 'Foto berhasil dihapus dari galeri.');
                })
                .catch(function (err) {
                    console.error('Delete photo error:', err);
                    thumb.style.opacity = '1';
                    btn.disabled = false;
                    showToast('error', 'Gagal', 'Foto gagal dihapus, coba lagi.');
                });
        });
    }

    // =============================================
    // ZOOM IMAGE (tombol close + Esc)
    // =============================================

    // Hapus video handler — tampilkan modal konfirmasi dulu
    var btnDeleteVideo = document.getElementById('btn-delete-video');
    if (btnDeleteVideo) {
        btnDeleteVideo.addEventListener('click', function () {
            showModal('confirm-delete-video');
        });
    }

    var confirmDeleteVideoBtn = document.getElementById('confirm-delete-video-btn');
    if (confirmDeleteVideoBtn) {
        confirmDeleteVideoBtn.addEventListener('click', function () {
            closeModal('confirm-delete-video');
            // Tambah hidden input untuk flag hapus video
            var existing = els.formEdit.querySelector('input[name="remove_video"]');
            if (!existing) {
                var input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'remove_video';
                input.value = '1';
                els.formEdit.appendChild(input);
            }
            // Sembunyikan video wrap
            var videoWrap = document.getElementById('edit-existing-video-wrap');
            if (videoWrap) videoWrap.classList.add('hidden');
            showToast('success', 'Siap', 'Video akan dihapus saat Anda menyimpan perubahan.');
        });
    }

    function zoomImage(src) {
        const overlay = document.createElement('div');
        overlay.className = 'fixed inset-0 z-[100] bg-gray-950/90 flex items-center justify-center p-4 cursor-zoom-out';
        overlay.innerHTML =
            '<button type="button" id="zoom-close-btn" ' +
            'class="absolute top-5 right-5 w-11 h-11 flex items-center justify-center bg-[#2d3748] hover:bg-[#E62C37] text-white rounded shadow-lg font-bold text-2xl z-[101]">&times;</button>' +
            '<img src="' + src + '" id="zoomed-img" class="max-w-full max-h-[85vh] shadow-2xl rounded border border-gray-800 opacity-0 transition-opacity duration-300">';
        document.body.appendChild(overlay);

        const img = overlay.querySelector('#zoomed-img');
        img.addEventListener('error', function () { img.style.border = '2px solid #E62C37'; });
        requestAnimationFrame(function () { img.style.opacity = '1'; });

        function closeZoom() {
            overlay.remove();
            document.removeEventListener('keydown', escHandler);
        }
        function escHandler(e) { if (e.key === 'Escape') closeZoom(); }

        overlay.addEventListener('click', closeZoom);
        overlay.querySelector('#zoom-close-btn').addEventListener('click', function (e) {
            e.stopPropagation();
            closeZoom();
        });
        document.addEventListener('keydown', escHandler);
    }

    // =============================================
    // VALIDASI CREATE
    // =============================================
    const submitCreateBtn = document.getElementById('submit-create-btn');
    if (submitCreateBtn) {
        submitCreateBtn.addEventListener('click', function () {
            let isValid = true;
            const title = document.getElementById('create-input-title').value.trim();
            const year = document.getElementById('create-input-year').value.trim();
            const date = document.getElementById('create-input-date').value.trim();
            const desc = document.getElementById('create-input-description').value.trim();

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

            if (dzCreate && dzCreate.getQueuedFiles().length > 0) {
                dzCreate.processQueue();
            } else {
                els.formCreate.submit();
            }
        });
    }

    // =============================================
    // EDIT: validasi -> KONFIRMASI -> submit
    // =============================================
    const submitEditBtn = document.getElementById('submit-edit-btn');
    if (submitEditBtn) {
        submitEditBtn.addEventListener('click', function () {
            let isValid = true;
            const title = document.getElementById('edit-title').value.trim();
            const year = document.getElementById('edit-year').value.trim();
            const date = document.getElementById('edit-date').value.trim();
            const desc = document.getElementById('edit-description').value.trim();

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

    const confirmUpdateBtn = document.getElementById('confirm-update-proceed-btn');
    if (confirmUpdateBtn) {
        confirmUpdateBtn.addEventListener('click', function () {
            closeModal('confirm-update');

            const form = els.formEdit;
            if (dzEdit && dzEdit.getQueuedFiles().length > 0) {
                dzEdit.options.url = form.action;
                dzEdit.processQueue();
            } else {
                fetch(form.action, {
                    method: 'POST',
                    body: new FormData(form),
                    headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }
                })
                    .then(function (res) { if (!res.ok) throw new Error('HTTP ' + res.status); return res.json(); })
                    .then(function () {
                        showToast('success', 'Berhasil Diubah!', 'Data pencapaian telah diperbarui.');
                        setTimeout(function () { location.reload(); }, 1000);
                    })
                    .catch(function (err) {
                        console.error('Update error:', err);
                        showToast('error', 'Gagal!', 'Terjadi kesalahan saat memperbarui data.');
                    });
            }
        });
    }

    // =============================================
    // DROPZONE — lazy init (setelah modal visible)
    // =============================================
    var dzCreateInitialized = false;
    var dzEditInitialized = false;

    function initDzCreate() {
        if (dzCreateInitialized) return;
        try {
            var Dropzone = window.Dropzone;
            if (!Dropzone || !els.formCreate) {
                console.warn('[INFO] Dropzone tidak ter-load, fallback ke form submit biasa.');
                return;
            }
            Dropzone.autoDiscover = false;

            dzCreate = new Dropzone('#dropzone-create', {
                url: CFG.storeUrl,
                autoProcessQueue: false,
                uploadMultiple: true,
                parallelUploads: 10,
                paramName: 'images',
                maxFilesize: 50,
                acceptedFiles: 'image/jpeg,image/png,image/jpg,image/gif,video/mp4,video/mov,video/webm,video/avi',
                addRemoveLinks: true,
                dictDefaultMessage: 'Tarik file foto/video ke sini atau klik untuk memilih',
                dictRemoveFile: 'Hapus',
                headers: { 'X-CSRF-TOKEN': CFG.csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            dzCreate.on('sendingmultiple', function (data, xhr, fd) {
                var formEls = els.formCreate.elements;
                for (var i = 0; i < formEls.length; i++) {
                    var el = formEls[i];
                    if (!el.name || el.type === 'file') continue;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked) fd.append(el.name, el.value);
                    } else {
                        fd.append(el.name, el.value);
                    }
                }
            });
            dzCreate.on('successmultiple', function (files, res) {
                showToast('success', 'Berhasil!', 'Portofolio baru aviary telah ditambahkan.');
                setTimeout(function () { location.reload(); }, 1000);
            });
            dzCreate.on('errormultiple', function (files, res) {
                console.error('Dropzone create error:', res);
                var msg = 'Terjadi kesalahan saat upload foto.';
                if (typeof res === 'object' && res !== null) {
                    msg = res.message || res.error || (res.errors ? Object.values(res.errors).flat().join(', ') : msg);
                } else if (typeof res === 'string') {
                    try {
                        var parsed = JSON.parse(res);
                        msg = parsed.message || parsed.error || msg;
                    } catch (e) {
                        msg = res;
                    }
                }
                showToast('error', 'Gagal!', msg);
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
                headers: { 'X-CSRF-TOKEN': CFG.csrfToken, 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
            });
            dzEdit.on('sendingmultiple', function (data, xhr, fd) {
                var formEls = els.formEdit.elements;
                for (var i = 0; i < formEls.length; i++) {
                    var el = formEls[i];
                    if (!el.name || el.type === 'file') continue;
                    if (el.type === 'checkbox' || el.type === 'radio') {
                        if (el.checked) fd.append(el.name, el.value);
                    } else {
                        fd.append(el.name, el.value);
                    }
                }
            });
            dzEdit.on('successmultiple', function () {
                showToast('success', 'Berhasil Diubah!', 'Data pencapaian dan galeri foto telah diperbarui.');
                setTimeout(function () { location.reload(); }, 1000);
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
    // EXPOSE FUNGSI YANG DIPANGGIL DARI onclick="" DI BLADE
    // =============================================
    window.openCreateModal = openCreateModal;
    window.openEditModal = openEditModal;
    window.openDeleteModal = openDeleteModal;
    window.closeModal = closeModal;
    window.zoomImage = zoomImage;

    // Session flash ditangani oleh admin.blade.php, tidak perlu duplikat di sini
})();