// Ekspor .xlsx bergaya untuk laporan admin (Keuangan, Vaksinasi, Telur, Stok).
// exceljs dipilih karena styling (warna header, border, merged title) tidak
// tersedia di SheetJS community. Dynamic import = chunk terpisah yang baru
// terunduh saat tombol Export diklik pertama kali (bundle utama tetap ramping).
//
// API: window.eksporExcel(btn, { namaFile, judul, headers, rows, ringkasan? })
// ringkasan (opsional, khusus keuangan): { rows: [[label, nilai], ...] } —
// dicetak sebagai blok terpisah yang rapi di bawah tabel, bukan nyempil di data.
window.eksporExcel = async function (btn, { namaFile, judul, headers, rows, ringkasan }) {
    const teksAwal = btn ? btn.textContent : "";
    const kunci = () => {
        if (!btn) return;
        btn.disabled = true;
        btn.textContent = "Menyiapkan...";
        btn.classList.add("opacity-60", "cursor-not-allowed");
    };
    const bebas = () => {
        if (!btn) return;
        btn.disabled = false;
        btn.textContent = teksAwal;
        btn.classList.remove("opacity-60", "cursor-not-allowed");
    };

    kunci();
    try {
        const ExcelJS = await import("exceljs");
        const wb = new ExcelJS.Workbook();
        const ws = wb.addWorksheet(judul.slice(0, 31));

        // Formula-injection guard: nilai yang diawali =+-@ diawali apostrof
        const aman = (v) => (typeof v === "string" && /^[=+\-@]/.test(v) ? "'" + v : v);

        // Judul: merged bold di tengah atas
        ws.mergeCells(1, 1, 1, headers.length);
        const selJudul = ws.getCell(1, 1);
        selJudul.value = judul;
        selJudul.font = { bold: true, size: 14, color: { argb: "FF1A1A2E" } };
        selJudul.alignment = { horizontal: "center", vertical: "middle" };
        ws.getRow(1).height = 26;

        // Header tabel: baris berwarna + bold + border
        const barisHeader = ws.getRow(3);
        headers.forEach((h, i) => {
            const sel = barisHeader.getCell(i + 1);
            sel.value = h;
            sel.font = { bold: true, size: 11, color: { argb: "FF1A1A2E" } };
            sel.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFFF176" } };
            sel.alignment = { horizontal: "center", vertical: "middle" };
        });
        barisHeader.height = 20;

        // Data + border tiap sel
        rows.forEach((r) => {
            const baris = ws.addRow(r.map(aman));
            baris.eachCell((sel) => {
                sel.border = {
                    top: { style: "thin", color: { argb: "FFCCCCCC" } },
                    bottom: { style: "thin", color: { argb: "FFCCCCCC" } },
                    left: { style: "thin", color: { argb: "FFCCCCCC" } },
                    right: { style: "thin", color: { argb: "FFCCCCCC" } },
                };
            });
        });

        // Blok ringkasan terpisah (khusus keuangan)
        if (ringkasan && Array.isArray(ringkasan.rows) && ringkasan.rows.length) {
            ws.addRow([]); // jarak kosong antar tabel & ringkasan
            const awal = ws.rowCount + 1;
            ringkasan.rows.forEach(([label, nilai], i) => {
                const baris = ws.getRow(awal + i);
                const selLabel = baris.getCell(1);
                const selNilai = baris.getCell(2);
                selLabel.value = aman(label);
                selNilai.value = typeof nilai === "number" ? nilai : aman(nilai);
                selLabel.font = { bold: true, size: 11 };
                selNilai.font = { bold: i === ringkasan.rows.length - 1, size: 11 };
                selNilai.numFmt = typeof nilai === "number" ? "#,##0" : undefined;
                selNilai.alignment = { horizontal: "right" };
            });
            // Garis atas & bawah blok ringkasan ala laporan
            ws.getRow(awal).eachCell((sel) => {
                sel.border = { top: { style: "medium", color: { argb: "FF1A1A2E" } } };
            });
            ws.getRow(awal + ringkasan.rows.length - 1).eachCell((sel) => {
                sel.border = {
                    bottom: { style: "double", color: { argb: "FF1A1A2E" } },
                };
            });
        }

        // Lebar kolom otomatis (dari header + data + label ringkasan)
        headers.forEach((h, i) => {
            const lebar = Math.max(
                String(h).length + 4,
                ...rows.map((r) => String(r[i] ?? "").length + 2),
                12,
            );
            ws.getColumn(i + 1).width = Math.min(lebar, 50);
        });

        wb.xlsx.writeBuffer().then((buf) => {
            const blob = new Blob([buf], {
                type: "application/vnd.openxmlformats-officedocument.spreadsheetml.sheet",
            });
            const url = URL.createObjectURL(blob);
            const a = document.createElement("a");
            a.href = url;
            a.download = namaFile;
            a.click();
            URL.revokeObjectURL(url);
            if (window.showToast)
                showToast("success", "Berhasil", namaFile + " terunduh.");
        });
    } catch (err) {
        console.error("Ekspor Excel gagal:", err);
        if (window.showToast)
            showToast("error", "Gagal", "Ekspor Excel gagal. Coba lagi.");
    } finally {
        bebas();
    }
};
