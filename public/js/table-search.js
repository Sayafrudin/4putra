/**
 * table-search.js v2
 * Client-side search/filter untuk tabel admin.
 * Input search harus ditempatkan SEBELUM div.table-search-wrapper.
 */
(function () {
    'use strict';

    document.querySelectorAll('.table-search-input').forEach(function (input) {
        // Cari wrapper tabel: naik ke parent, lalu cari sibling .table-search-wrapper
        var tableWrapper = null;
        var el = input;
        while (el && el !== document.body) {
            var sibling = el.nextElementSibling;
            while (sibling) {
                if (sibling.classList && sibling.classList.contains('table-search-wrapper')) {
                    tableWrapper = sibling;
                    break;
                }
                // Cek juga di dalam sibling (nested)
                var nested = sibling.querySelector('.table-search-wrapper');
                if (nested) {
                    tableWrapper = nested;
                    break;
                }
                sibling = sibling.nextElementSibling;
            }
            if (tableWrapper) break;
            el = el.parentElement;
        }

        if (!tableWrapper) return;
        var tbody = tableWrapper.querySelector('tbody');
        if (!tbody) return;

        // Simpan empty row sekali saja
        var emptyRow = null;
        var emptyRowOriginalText = '';
        var rows = tbody.querySelectorAll('tr');
        rows.forEach(function (row) {
            if (row.querySelector('td[colspan]')) {
                emptyRow = row;
                emptyRowOriginalText = row.querySelector('td').textContent;
            }
        });

        input.addEventListener('input', function () {
            var query = input.value.toLowerCase().trim();
            var allRows = tbody.querySelectorAll('tr');
            var visibleCount = 0;

            allRows.forEach(function (row) {
                if (row === emptyRow) return;
                var text = row.textContent.toLowerCase();
                if (!query || text.indexOf(query) !== -1) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            // Tampilkan/hide empty message
            if (emptyRow) {
                if (visibleCount === 0 && query) {
                    emptyRow.style.display = '';
                    var td = emptyRow.querySelector('td');
                    if (td) td.textContent = 'Tidak ditemukan data yang cocok dengan "' + input.value.trim() + '".';
                } else if (visibleCount === 0 && !query) {
                    emptyRow.style.display = '';
                    var td2 = emptyRow.querySelector('td');
                    if (td2) td2.textContent = emptyRowOriginalText;
                } else {
                    emptyRow.style.display = 'none';
                }
            }
        });
    });
})();