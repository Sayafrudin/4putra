import './bootstrap';
import './script.js';
import '@hotwired/turbo';
import Alpine from 'alpinejs'
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

window.Dropzone = Dropzone;
window.Alpine = Alpine
Alpine.start()

// Bersihkan snapshot Turbo saat switch bahasa agar tidak restore konten bahasa lama
document.addEventListener('click', (e) => {
    if (e.target.closest('a[href*="/lang/"]')) window.Turbo?.cache?.clear();
}, true);