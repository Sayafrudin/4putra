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

// Turbo mengganti <body> tapi <html> tidak — overflow:hidden milik modal/lightbox
// bisa yatim dan mematikan scroll permanen. Reset sebelum render & sebelum cache.
const resetScrollLock = () => {
    document.documentElement.style.overflow = '';
    document.body.style.overflow = '';
};
document.addEventListener('turbo:before-render', resetScrollLock);
document.addEventListener('turbo:before-cache', resetScrollLock);