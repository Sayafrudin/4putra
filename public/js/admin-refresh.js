// Refresh daftar admin tanpa reload halaman penuh:
// fetch ulang halaman saat ini lalu ganti kontainer [data-admin-list] dengan versi terbaru.
// Tombol baris memakai inline onclick sehingga tetap berfungsi setelah swap.
function refreshAdminList() {
    var container = document.querySelector('[data-admin-list]');
    if (!container) { location.reload(); return; }

    fetch(location.href, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
        .then(function (r) { if (!r.ok) throw new Error('HTTP ' + r.status); return r.text(); })
        .then(function (html) {
            var fresh = new DOMParser().parseFromString(html, 'text/html').querySelector('[data-admin-list]');
            if (!fresh) throw new Error('Kontainer daftar tidak ditemukan');
            container.replaceWith(fresh);
        })
        .catch(function (e) {
            console.error('Refresh daftar gagal:', e);
            location.reload(); // fallback aman
        });
}
window.refreshAdminList = refreshAdminList;

// Hapus data: intercept semua form _method=DELETE (komponen modal-confirm-delete) ->
// satu fetch + refresh daftar, tanpa POST redirect + reload penuh.
document.addEventListener('submit', function (e) {
    var form = e.target;
    var methodInput = form.querySelector('input[name="_method"]');
    if (!methodInput || methodInput.value.toUpperCase() !== 'DELETE') return;

    e.preventDefault();
    var btn = form.querySelector('[type="submit"]');
    if (btn) { btn.disabled = true; btn.textContent = 'Menghapus...'; }

    fetch(form.action, {
        method: 'POST',
        body: new FormData(form),
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
        credentials: 'same-origin'
    })
        .then(function (r) {
            if (r.redirected && r.url.indexOf('/login') !== -1) {
                showToast('error', 'Sesi Berakhir', 'Sesi Anda telah berakhir. Memuat ulang...');
                setTimeout(function () { location.href = r.url; }, 1500);
                throw new Error('session');
            }
            if (r.status === 419) {
                showToast('error', 'Sesi Berakhir', 'Sesi Anda telah berakhir. Halaman akan dimuat ulang...');
                setTimeout(function () { location.reload(); }, 2000);
                throw new Error('session');
            }
            if (!r.ok) throw new Error('HTTP ' + r.status);
        })
        .then(function () {
            var modalRoot = form.closest('[id^="modal-"]');
            if (modalRoot) { modalRoot.classList.add('hidden'); modalRoot.classList.remove('flex'); }
            if (window.showToast) showToast('success', 'Terhapus!', 'Data berhasil dihapus.');
            refreshAdminList();
        })
        .catch(function (err) {
            if (err.message === 'session') return;
            console.error('Delete error:', err);
            if (window.showToast) showToast('error', 'Gagal!', 'Terjadi kesalahan saat menghapus data.');
            if (btn) { btn.disabled = false; btn.textContent = 'Ya, Hapus'; }
        });
});
