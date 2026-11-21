<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Kandang;
use App\Models\Vaksinasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Obat & Vaksinasi burung — halaman ala kandang: section per blok, card per
 * kandang berisi chip burung + badge status (hijau = terlindungi, merah =
 * jatuh tempo). Klik card kandang → modal catat per burung / massal kandang.
 * Siklus ulang per metode: suntik = 1x/tahun (12 bulan), oral = tiap 3 bulan.
 * Notifikasi WA otomatis menyusul pasca-skripsi; saat ini deteksi jatuh tempo
 * dihitung server-side di halaman ini + dashboard.
 */
class VaksinasiController extends Controller
{
    use LogsActivity;

    public function index()
    {
        // Eager: burung + kandang dalam 2 query (tanpa N+1).
        $burungSemua = Burung::with('kandang:id,blok,nomor,tipe')->orderBy('nama')->get();

        // Catatan terakhir per burung (semua metode) → status badge di chip.
        $terakhirPerBurung = Vaksinasi::catatanTerakhirPerBurung()->keyBy('burung_id');

        $riwayat = Vaksinasi::with('burung')->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();

        // Papan ala kandang: burung dikelompokkan per kandang, lalu per blok.
        $perKandang = $burungSemua
            ->whereNotNull('kandang_id')
            ->groupBy('kandang_id')
            ->map(fn ($grup) => [
                'kandang' => $grup->first()->kandang,
                'burung' => $grup->sortBy('nama')->values(),
            ])
            ->sortBy(fn ($item) => $item['kandang']->label, SORT_NATURAL | SORT_FLAG_CASE)
            ->groupBy(fn ($item) => $item['kandang']->blok);

        // Burung bebas (belum ditempatkan) — tetap bisa dicatat.
        $tanpaKandang = $burungSemua->whereNull('kandang_id')->sortBy('nama')->values();

        // Jatuh tempo: siklus per metode (suntik 12 bulan, oral 3 bulan).
        $jatuhTempo = $terakhirPerBurung
            ->filter(fn ($v) => $v->sudah_jatuh_tempo)
            ->sortBy('tanggal_vaksin')
            ->values();

        return view('admin.vaksinasi.index', compact('perKandang', 'tanpaKandang', 'terakhirPerBurung', 'jatuhTempo', 'riwayat'));
    }

    /** Halaman detail satu kandang: chip burung + dot status, catat per burung/massal, riwayat lengkap kandang ini. */
    public function detail(Kandang $kandang)
    {
        $burungList = $kandang->burungs()->orderBy('nama')->get();
        $terakhirPerBurung = Vaksinasi::catatanTerakhirPerBurung()->keyBy('burung_id');
        $riwayat = Vaksinasi::with('burung')
            ->whereIn('burung_id', $burungList->pluck('id'))
            ->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();

        return view('admin.vaksinasi.detail', compact('kandang', 'burungList', 'terakhirPerBurung', 'riwayat'));
    }

    /** PDF riwayat satu kandang (dipakai tombol Export PDF di halaman detail). */
    public function exportDetailPdf(Request $request, Kandang $kandang)
    {
        try {
            $burungList = $kandang->burungs()->orderBy('nama')->get();
            $riwayat = Vaksinasi::with('burung')
                ->whereIn('burung_id', $burungList->pluck('id'))
                ->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vaksinasi.detail-pdf', compact('kandang', 'burungList', 'riwayat'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_vaksinasi_pdf', 'Mengekspor riwayat obat & vaksinasi kandang '.$kandang->label.' PDF ('.$riwayat->count().' catatan)');

            return $pdf->download('riwayat_vaksinasi_'.$kandang->label.'_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF vaksinasi kandang', ['kandang' => $kandang->id, 'error' => $e->getMessage()]);

            return redirect()->route('admin.vaksinasi.detail', $kandang)->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $riwayat = Vaksinasi::with('burung.kandang:id,blok,nomor,tipe')->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vaksinasi.pdf', compact('riwayat'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_vaksinasi_pdf', 'Mengekspor laporan obat & vaksinasi PDF ('.$riwayat->count().' catatan)');

            return $pdf->download('laporan_obat_vaksinasi_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF vaksinasi', ['error' => $e->getMessage()]);

            return redirect()->route('admin.vaksinasi.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'burung_id' => ['nullable', 'integer', 'exists:burungs,id'],
            'burung_ids' => ['nullable', 'array', 'max:50'],
            'burung_ids.*' => ['integer', 'exists:burungs,id'],
            'jenis' => ['required', 'string', 'max:100'],
            'metode' => ['required', 'in:suntik,oral'],
            'dosis' => ['nullable', 'array', 'max:50'],
            'dosis.*' => ['nullable', 'string', 'max:100'],
            'tanggal_vaksin' => ['required', 'date', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        // Satu target: burung_id tunggal atau daftar massal (catat semua kandang).
        $ids = isset($data['burung_ids']) ? array_values(array_unique($data['burung_ids'])) : [$data['burung_id']];
        if (empty($ids) || $ids[0] === null) {
            return response()->json(['ok' => false, 'message' => 'Pilih burung tujuan terlebih dahulu.'], 422);
        }

        try {
            $baris = [];
            $now = now();
            foreach ($ids as $id) {
                $baris[] = [
                    'burung_id' => $id,
                    'jenis' => $data['jenis'],
                    'metode' => $data['metode'],
                    'dosis' => $data['dosis'][$id] ?? ($data['dosis'][(string) $id] ?? null),
                    'tanggal_vaksin' => $data['tanggal_vaksin'],
                    'catatan' => $data['catatan'] ?? null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
            Vaksinasi::insert($baris);
            Cache::forget('dashboard.stats');

            $label = count($ids) === 1 ? 'burung #'.$ids[0] : count($ids).' burung (massal)';
            $this->logActivity($request, 'tambah_vaksinasi', 'Mencatat '.$data['jenis'].' ('.$data['metode'].') untuk '.$label);

            return response()->json(['ok' => true, 'message' => $data['jenis'].' ('.$data['metode'].') dicatat untuk '.$label.'.']);
        } catch (\Throwable $e) {
            Log::error('Gagal catat vaksinasi', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, Vaksinasi $vaksinasi)
    {
        try {
            $jenis = $vaksinasi->jenis;
            $burungId = $vaksinasi->burung_id;
            $vaksinasi->delete();
            Cache::forget('dashboard.stats');
            $this->logActivity($request, 'hapus_vaksinasi', 'Menghapus catatan '.$jenis.' burung #'.$burungId);

            return response()->json(['ok' => true, 'message' => 'Catatan "'.$jenis.'" dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus vaksinasi', ['id' => $vaksinasi->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus catatan. Coba lagi.'], 500);
        }
    }
}
