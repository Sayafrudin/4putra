/**
 * table-search.js v3
 * Client-side search/filter untuk tabel admin.
 * Input search harus ditempatkan SEBELUM div.table-search-wrapper.
 *
 * v3: resolve wrapper/tbody/empty-row SETIAP keystroke (bukan sekali di load) —
 * admin-refresh.js mengganti <tbody data-admin-list> setelah CRUD sukses,
 * referensi lama menjadi yatim sehingga search mati (bug lama v2).
 */
(function () {
    'use strict';

    function findWrapper(inputEl) {
        var el = inputEl;
        while (el && el !== document.body) {
            var sibling = el.nextElementSibling;
            while (sibling) {
                if (sibling.classList && sibling.classList.contains('table-search-wrapper')) {
                    return sibling;
                }
                // Cek juga di dalam sibling (nested)
                var nested = sibling.querySelector('.table-search-wrapper');
                if (nested) {
                    return nested;
                }
                sibling = sibling.nextElementSibling;
            }
            el = el.parentElement;
        }
        return null;
    }

    document.querySelectorAll('.table-search-input').forEach(function (input) {
        input.addEventListener('input', function () {
            var tableWrapper = findWrapper(input);
            if (!tableWrapper) return;
            var tbody = tableWrapper.querySelector('tbody');
            if (!tbody) return;

            var query = input.value.toLowerCase().trim();
            var visibleCount = 0;
            var emptyRow = null;

            tbody.querySelectorAll('tr').forEach(function (row) {
                if (row.querySelector('td[colspan]')) {
                    emptyRow = row;
                    return;
                }
                var text = row.textContent.toLowerCase();
                if (!query || text.indexOf(query) !== -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (!emptyRow) return;
            var td = emptyRow.querySelector('td');
            if (!td) return;
            // Simpan teks asli sekali; dataset ikut hilang saat tbody diganti (aman)
            if (!td.dataset.originalText) td.dataset.originalText = td.textContent;
            if (visibleCount === 0 && query) {
                emptyRow.style.display = '';
                td.textContent = 'Tidak ditemukan data yang cocok dengan "' + input.value.trim() + '".';
            } else if (visibleCount === 0) {
                emptyRow.style.display = '';
                td.textContent = td.dataset.originalText;
            } else {
                emptyRow.style.display = 'none';
            }
        });
    });
})();
