/**
 * media-protect.js v2
 * Proteksi gambar & video di website publik + fitur zoom gambar:
 * - Blok klik kanan pada gambar/video
 * - Blok drag & drop pada gambar/video
 * - Blok shortcut keyboard untuk download (Ctrl+S, Ctrl+U, F12, Ctrl+Shift+I/J)
 * - Disable devtools detection
 * - Zoom gambar saat diklik dengan watermark overlay & tombol (X) untuk keluar
 * - user-select: none pada semua gambar
 */
(function () {
    'use strict';

    // Guard: cegah duplikasi style & listener saat Turbo Drive mengeksekusi ulang script ini
    if (window.zoomMedia) return;

    // Inject CSS
    var style = document.createElement('style');
    style.textContent =
        '.zoom-media-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;cursor:zoom-out;opacity:0;transition:opacity .25s ease;overflow:hidden}' +
        '.zoom-media-close{position:absolute;top:16px;right:16px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#2d3748;color:white;border:none;border-radius:8px;font-size:24px;font-weight:bold;cursor:pointer;transition:background .2s;z-index:10001;line-height:1}' +
        '.zoom-media-close:hover{background:#E62C37}' +
        '.zoom-media-img{object-fit:contain;max-width:92vw;max-height:88vh;box-shadow:0 25px 50px rgba(0,0,0,0.5);transform:scale(0.9);transition:transform .25s ease;cursor:default;user-select:none;-webkit-user-select:none;pointer-events:none}' +
        '.zoom-media-nav{position:absolute;top:50%;transform:translateY(-50%);width:48px;height:48px;display:flex;align-items:center;justify-content:center;background:rgba(45,55,72,.85);color:#fff;border:none;border-radius:50%;font-size:26px;cursor:pointer;transition:background .2s;z-index:10001}' +
        '.zoom-media-nav:hover{background:#E62C37}' +
        '.zoom-media-prev{left:16px}' +
        '.zoom-media-next{right:16px}' +
        '.zoom-media-count{position:absolute;bottom:16px;left:50%;transform:translateX(-50%);background:rgba(0,0,0,.6);color:#fff;font-size:13px;font-weight:600;padding:6px 14px;border-radius:9999px;z-index:10001}' +
        '.zoom-media-watermark{position:absolute;inset:0;z-index:10000;pointer-events:none;display:flex;flex-wrap:wrap;align-content:center;justify-content:center;gap:80px;opacity:.12;transform:rotate(-30deg)}' +
        '.zoom-media-watermark span{font-size:20px;font-weight:800;color:#fff;text-transform:uppercase;letter-spacing:4px;white-space:nowrap}' +
        'img,video{-webkit-user-select:none;-webkit-touch-callout:none;user-select:none}';
    document.head.appendChild(style);

    // =============================================
    // BLOK KLIK KANAN & DRAG
    // =============================================
    document.addEventListener('contextmenu', function (e) {
        var tag = e.target.tagName;
        if (tag === 'IMG' || tag === 'VIDEO') {
            e.preventDefault();
        }
    });

    document.addEventListener('dragstart', function (e) {
        var tag = e.target.tagName;
        if (tag === 'IMG' || tag === 'VIDEO') {
            e.preventDefault();
        }
    });

    // =============================================
    // BLOK SHORTCUT KEYBOARD
    // =============================================
    document.addEventListener('keydown', function (e) {
        // Ctrl+S / Cmd+S
        if ((e.ctrlKey || e.metaKey) && e.key === 's') {
            e.preventDefault();
        }
        // Ctrl+U / Cmd+U (view source)
        if ((e.ctrlKey || e.metaKey) && e.key === 'u') {
            e.preventDefault();
        }
        // F12
        if (e.key === 'F12') {
            e.preventDefault();
        }
        // Ctrl+Shift+I (DevTools)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'I' || e.key === 'i')) {
            e.preventDefault();
        }
        // Ctrl+Shift+J (Console)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'J' || e.key === 'j')) {
            e.preventDefault();
        }
        // Ctrl+Shift+C (Element inspector)
        if ((e.ctrlKey || e.metaKey) && e.shiftKey && (e.key === 'C' || e.key === 'c')) {
            e.preventDefault();
        }
    });

    // =============================================
    // DISABLE VIDEO DOWNLOAD BUTTON
    // =============================================
    function protectVideos() {
        document.querySelectorAll('video').forEach(function (v) {
            v.setAttribute('controlsList', 'nodownload noplaybackrate');
            v.setAttribute('oncontextmenu', 'return false');
            v.removeAttribute('download');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', protectVideos);
    } else {
        protectVideos();
    }
    // Jaga-jaga jika ada video yang di-inject secara dinamis
    // (debounce via rAF: batch banyak mutasi jadi satu scan per frame)
    var rafPending = false;
    var videoObserver = new MutationObserver(function () {
        if (rafPending) return;
        rafPending = true;
        requestAnimationFrame(function () {
            rafPending = false;
            protectVideos();
        });
    });
    // Observe documentElement agar bertahan saat Turbo menggantikan body
    videoObserver.observe(document.documentElement, { childList: true, subtree: true });

    // =============================================
    // ZOOM GAMBAR DENGAN WATERMARK (+ galeri multi-foto opsional)
    // zoomMedia(src)              → perilaku lama: 1 gambar
    // zoomMedia(src, photos)      → mode galeri: prev/next + counter + panah
    //                               photos = [{thumb, full}]
    // =============================================
    window.zoomMedia = function (src, photos) {
        var gallery = Array.isArray(photos) && photos.length > 1
            ? photos.map(function (p) { return p.full || p.thumb || p; })
            : null;

        var overlay = document.createElement('div');
        overlay.className = 'zoom-media-overlay';

        // Watermark text pattern
        var wmText = '4PUTRA VERTEX AVIARY';
        var wmHtml = '';
        for (var i = 0; i < 12; i++) {
            wmHtml += '<span>' + wmText + '</span>';
        }

        overlay.innerHTML =
            '<button class="zoom-media-close" aria-label="Tutup">&times;</button>' +
            '<div class="zoom-media-watermark">' + wmHtml + '</div>' +
            '<img src="' + src + '" class="zoom-media-img" alt="Gambar diperbesar" draggable="false">';

        document.body.appendChild(overlay);
        document.body.style.overflow = 'hidden';

        // Animasi masuk
        requestAnimationFrame(function () {
            overlay.style.opacity = '1';
            overlay.querySelector('.zoom-media-img').style.transform = 'scale(1)';
        });

        function escHandler(e) {
            if (e.key === 'Escape') {
                // Hentikan bubbling agar modal detail di belakang tidak ikut tertutup
                e.stopPropagation();
                closeZoom();
            }
            if (gallery) {
                if (e.key === 'ArrowLeft') showAt(pos - 1);
                if (e.key === 'ArrowRight') showAt(pos + 1);
            }
        }

        var img = overlay.querySelector('.zoom-media-img');
        var pos = gallery ? Math.max(0, gallery.indexOf(src)) : 0;

        function showAt(n) {
            if (!gallery) return;
            pos = (n + gallery.length) % gallery.length; // wrap-around
            img.src = gallery[pos];
            var counter = overlay.querySelector('.zoom-media-count');
            if (counter) counter.textContent = (pos + 1) + ' / ' + gallery.length;
        }

        if (gallery && gallery.length > 1) {
            var prev = document.createElement('button');
            prev.className = 'zoom-media-nav zoom-media-prev';
            prev.setAttribute('aria-label', 'Foto sebelumnya');
            prev.innerHTML = '&#8249;';
            prev.addEventListener('click', function (e) { e.stopPropagation(); showAt(pos - 1); });

            var next = document.createElement('button');
            next.className = 'zoom-media-nav zoom-media-next';
            next.setAttribute('aria-label', 'Foto berikutnya');
            next.innerHTML = '&#8250;';
            next.addEventListener('click', function (e) { e.stopPropagation(); showAt(pos + 1); });

            var counter = document.createElement('span');
            counter.className = 'zoom-media-count';
            counter.textContent = (pos + 1) + ' / ' + gallery.length;

            overlay.appendChild(prev);
            overlay.appendChild(next);
            overlay.appendChild(counter);

            // Swipe mobile: geser kiri/kanan
            var touchX = null;
            overlay.addEventListener('touchstart', function (e) { touchX = e.touches[0].clientX; }, { passive: true });
            overlay.addEventListener('touchend', function (e) {
                if (touchX === null) return;
                var dx = e.changedTouches[0].clientX - touchX;
                if (Math.abs(dx) > 40) showAt(dx < 0 ? pos + 1 : pos - 1);
                touchX = null;
            }, { passive: true });
        }

        function closeZoom() {
            document.removeEventListener('keydown', escHandler);
            overlay.style.opacity = '0';
            overlay.querySelector('.zoom-media-img').style.transform = 'scale(0.9)';
            setTimeout(function () {
                overlay.remove();
                document.body.style.overflow = '';
            }, 250);
        }

        overlay.addEventListener('click', function (e) {
            if (e.target === overlay) closeZoom();
        });
        overlay.querySelector('.zoom-media-close').addEventListener('click', function (e) {
            e.stopPropagation();
            closeZoom();
        });
        document.addEventListener('keydown', escHandler);
    };
})();