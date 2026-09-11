import './bootstrap';
import './script.js';
import '@hotwired/turbo';
import Alpine from 'alpinejs'
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

window.Dropzone = Dropzone;
window.Alpine = Alpine
Alpine.start()

// Bar loading Turbo muncul seketika saat navigasi (feedback instan, penting di mobile)
window.Turbo.config.drive.progressBarDelay = 0;

// Bersihkan snapshot Turbo saat switch bahasa agar tidak restore konten bahasa lama
document.addEventListener('click', (e) => {
    if (e.target.closest('a[href*="/lang/"]')) window.Turbo?.cache?.clear();
}, true);

// State DOM transient (lightbox/menu/overlay zoom) jangan ikut snapshot Turbo —
// elemen hasil restore yatim tanpa listener: drawer macet terbuka (hamburger mati
// karena data-nav-init ter-cache) dan overlay zoom menelan semua tap (scroll mati).
const resetTransientUI = () => {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
    document.querySelectorAll('.zoom-media-overlay').forEach((o) => o.remove());
    const menu = document.getElementById('mobile-menu');
    if (menu && !menu.classList.contains('invisible')) {
        menu.classList.add('invisible', 'opacity-0', '-translate-y-2');
        const burger = document.getElementById('hamburger-btn');
        if (burger) burger.setAttribute('aria-pressed', 'false');
    }
    const nav = document.getElementById('navbar');
    if (nav) delete nav.dataset.navInit;
};
document.addEventListener('turbo:before-render', resetTransientUI);
document.addEventListener('turbo:before-cache', resetTransientUI);