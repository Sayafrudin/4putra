// Ekspor .xlsx di sisi browser untuk laporan admin (Vaksinasi, Telur, Keuangan).
// SheetJS ('xlsx') sudah jadi dependency; dynamic import = chunk terpisah yang
// baru terunduh saat tombol Export diklik pertama kali (bundle utama tetap ramping).
window.eksporExcel = async function (btn, { namaFile, judul, headers, rows }) {
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
        const XLSX = await import("xlsx");
        // Formula-injection guard: nilai yang diawali =+-@ diawali apostrof agar
        // tidak dieksekusi sebagai formula saat laporan dibuka di Excel
        const aman = (v) =>
            typeof v === "string" && /^[=+\-@]/.test(v) ? "'" + v : v;
        const rowsAman = rows.map((r) => r.map(aman));
        const ws = XLSX.utils.aoa_to_sheet([headers, ...rowsAman]);
        ws["!cols"] = headers.map((h, i) => ({
            wch: Math.max(
                String(h).length + 2,
                ...rows.map((r) => String(r[i] ?? "").length + 2),
                12,
            ),
        }));
        const wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, judul.slice(0, 31));
        XLSX.writeFile(wb, namaFile);
        if (window.showToast)
            showToast("success", "Berhasil", namaFile + " terunduh.");
    } catch (err) {
        console.error("Ekspor Excel gagal:", err);
        if (window.showToast)
            showToast("error", "Gagal", "Ekspor Excel gagal. Coba lagi.");
    } finally {
        bebas();
    }
};
