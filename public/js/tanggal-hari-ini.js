/**
 * Tanggal kosong otomatis terisi hari ini (konvensi admin: tak pernah lupa isi tanggal).
 * Opt-in per input: atribut data-isi-hari-ini. Hanya dijalankan saat sebuah MODAL
 * terbuka (class hidden → flex) — input filter/rentang tidak pernah disentuh.
 * Turbo aman: observer terpasang sekali di body (body bertahan antar-navigasi).
 */
(function () {
    if (window.__tanggalHariIni) return;
    window.__tanggalHariIni = true;

    window.isiTanggalHariIni = function (id) {
        var el = document.getElementById(id);
        if (el && el.type === 'date' && !el.value) el.value = hariIni();
    };

    function hariIni() {
        // Format YYYY-MM-DD sesuai tanggal lokal peramban (WIB di perangkat petinggi).
        return new Date().toLocaleDateString('en-CA');
    }

    function isiModal(modal) {
        modal
            .querySelectorAll('input[type="date"][data-isi-hari-ini]')
            .forEach(function (el) {
                if (!el.value) el.value = hariIni();
            });
    }

    var observer = new MutationObserver(function (mutations) {
        mutations.forEach(function (m) {
            if (m.type !== 'attributes' || m.attributeName !== 'class') return;
            var t = m.target;
            if (
                t.classList.contains('fixed') &&
                t.classList.contains('flex') &&
                !t.classList.contains('hidden')
            ) {
                isiModal(t);
            }
        });
    });

    var mulai = function () {
        observer.observe(document.body, { attributes: true, attributeFilter: ['class'], subtree: true });
    };
    if (document.body) mulai();
    else document.addEventListener('DOMContentLoaded', mulai, { once: true });
})();
