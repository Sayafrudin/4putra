import './bootstrap';
import './script.js';
import Alpine from 'alpinejs'
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

window.Dropzone = Dropzone;
window.Alpine = Alpine
Alpine.start()