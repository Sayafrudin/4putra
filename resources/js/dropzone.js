// Chunk terpisah: hanya diunduh halaman yang punya .dropzone (admin upload)
import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

window.Dropzone = Dropzone;
Dropzone.autoDiscover = false;
