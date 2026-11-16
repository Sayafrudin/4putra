<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Vaksinasi;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Vaksinasi burung + deteksi booster bulanan.
 * Notifikasi WA otomatis (bot sebagai notifier) baru menyusul pasca-skripsi;
 * saat ini deteksi jatuh tempo dihitung server-side di halaman ini + dashboard.
 */
class VaksinasiController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $terakhir = Vaksinasi::vaksinTerakhirPerBurung();
        $jatuhTempo = $terakhir->filter(fn ($v) => $v->sudah_jatuh_tempo)->sortBy('tanggal_vaksin');
        $riwayat = Vaksinasi::with('burung')->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();
        $burungList = Burung::with('kandang:id,blok,nomor')->orderBy('nama')->get(['id', 'nama', 'spesies', 'fase', 'kandang_id']);

        return view('admin.vaksinasi.index', compact('terakhir', 'jatuhTempo', 'riwayat', 'burungList'));
    }

    public function exportPdf(Request $request)
    {
        try {
            $riwayat = Vaksinasi::with('burung.kandang:id,blok,nomor')->orderByDesc('tanggal_vaksin')->orderByDesc('id')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.vaksinasi.pdf', compact('riwayat'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_vaksinasi_pdf', 'Mengekspor laporan vaksinasi PDF ('.$riwayat->count().' catatan)');

            return $pdf->download('laporan_vaksinasi_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF vaksinasi', ['error' => $e->getMessage()]);

            return redirect()->route('admin.vaksinasi.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'burung_id' => ['required', 'integer', 'exists:burungs,id'],
            'jenis' => ['required', 'string', 'max:100'],
            'tanggal_vaksin' => ['required', 'date', 'before_or_equal:today'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $vaksinasi = Vaksinasi::create($data);
            $this->logActivity($request, 'tambah_vaksinasi', 'Mencatat vaksinasi '.$data['jenis'].' untuk burung #'.$data['burung_id']);

            return response()->json(['ok' => true, 'message' => 'Vaksinasi "'.$data['jenis'].'" dicatat.']);
        } catch (\Throwable $e) {
            Log::error('Gagal catat vaksinasi', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat vaksinasi. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, Vaksinasi $vaksinasi)
    {
        try {
            $jenis = $vaksinasi->jenis;
            $vaksinasi->delete();
            $this->logActivity($request, 'hapus_vaksinasi', 'Menghapus catatan vaksinasi '.$jenis);

            return response()->json(['ok' => true, 'message' => 'Catatan vaksinasi "'.$jenis.'" dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus vaksinasi', ['id' => $vaksinasi->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus catatan. Coba lagi.'], 500);
        }
    }
}
