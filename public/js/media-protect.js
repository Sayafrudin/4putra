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
        '.zoom-media-overlay{position:fixed;inset:0;z-index:9999;background:rgba(0,0,0,0.85);display:flex;align-items:center;justify-content:center;cursor:zoom-out;opacity:0;transition:opacity .25s ease;overflow:auto}' +
        '.zoom-media-close{position:absolute;top:16px;right:16px;width:44px;height:44px;display:flex;align-items:center;justify-content:center;background:#2d3748;color:white;border:none;border-radius:8px;font-size:24px;font-weight:bold;cursor:pointer;transition:background .2s;z-index:10001;line-height:1}' +
        '.zoom-media-close:hover{background:#E62C37}' +
        '.zoom-media-img{object-fit:contain;box-shadow:0 25px 50px rgba(0,0,0,0.5);transform:scale(0.9);transition:transform .25s ease;cursor:default;user-select:none;-webkit-user-select:none;pointer-events:none}' +
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
    var videoObserver = new MutationObserver(function () {
        protectVideos();
    });
    // Observe documentElement agar bertahan saat Turbo menggantikan body
    videoObserver.observe(document.documentElement, { childList: true, subtree: true });

    // =============================================
    // ZOOM GAMBAR DENGAN WATERMARK
    // =============================================
    window.zoomMedia = function (src) {
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

        function closeZoom() {
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
        document.addEventListener('keydown', function esc(e) {
            if (e.key === 'Escape') {
                closeZoom();
                document.removeEventListener('keydown', esc);
            }
        });
    };
})();