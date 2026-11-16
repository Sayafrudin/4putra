<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Keuangan farm: pemasukan, pengeluaran, gaji bulanan, budget pembangunan.
 * Jumlah divalidasi server-side; seluruh aksi ter-audit via LogsActivity.
 */
class KeuanganController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $tipe = in_array($request->query('tipe'), ['masuk', 'keluar']) ? $request->query('tipe') : null;
        $kategori = in_array($request->query('kategori'), Keuangan::KATEGORI) ? $request->query('kategori') : null;

        $dasar = Keuangan::query()
            ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori));

        $ringkasan = [
            'masuk' => (clone $dasar)->where('tipe', 'masuk')->sum('jumlah'),
            'keluar' => (clone $dasar)->where('tipe', 'keluar')->sum('jumlah'),
        ];
        $ringkasan['saldo'] = $ringkasan['masuk'] - $ringkasan['keluar'];

        $transaksis = (clone $dasar)->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        // Seluruh baris sesuai filter — sumber ekspor Excel sisi browser (farm-scale, ratusan baris)
        $semuaTransaksi = (clone $dasar)->orderByDesc('tanggal')->orderByDesc('id')->get();

        return view('admin.keuangan.index', [
            'transaksis' => $transaksis,
            'semuaTransaksi' => $semuaTransaksi,
            'ringkasan' => $ringkasan,
            'kategoriList' => Keuangan::KATEGORI,
            'tipeAktif' => $tipe,
            'kategoriAktif' => $kategori,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $tipe = in_array($request->query('tipe'), ['masuk', 'keluar']) ? $request->query('tipe') : null;
        $kategori = in_array($request->query('kategori'), Keuangan::KATEGORI) ? $request->query('kategori') : null;

        try {
            $query = Keuangan::query()
                ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
                ->when($kategori, fn ($q) => $q->where('kategori', $kategori));

            $transaksis = (clone $query)->orderByDesc('tanggal')->orderByDesc('id')->get();
            $ringkasan = [
                'masuk' => (clone $query)->where('tipe', 'masuk')->sum('jumlah'),
                'keluar' => (clone $query)->where('tipe', 'keluar')->sum('jumlah'),
            ];
            $ringkasan['saldo'] = $ringkasan['masuk'] - $ringkasan['keluar'];
            $tipeAktif = $tipe;
            $kategoriAktif = $kategori;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.keuangan.pdf', compact('transaksis', 'ringkasan', 'tipeAktif', 'kategoriAktif'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_keuangan_pdf', 'Mengekspor laporan keuangan PDF ('.$transaksis->count().' transaksi'.($tipe ? ', tipe '.$tipe : '').($kategori ? ', kategori '.$kategori : '').')');

            return $pdf->download('laporan_keuangan_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF keuangan', ['error' => $e->getMessage()]);

            return redirect()->route('admin.keuangan.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori' => ['required', 'in:'.implode(',', Keuangan::KATEGORI)],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'tanggal' => ['required', 'date', 'before_or_equal:today'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $trx = Keuangan::create($data);
            $this->logActivity($request, 'tambah_keuangan', 'Mencatat keuangan '.$data['tipe'].' '.$trx->jumlah_format.' ('.$data['kategori'].')');

            return response()->json(['ok' => true, 'message' => 'Transaksi '.$data['tipe'].' dicatat.']);
        } catch (\Throwable $e) {
            Log::error('Gagal catat keuangan', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat transaksi. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, Keuangan $keuangan)
    {
        try {
            $info = $keuangan->jumlah_format.' ('.$keuangan->kategori.')';
            $keuangan->delete();
            $this->logActivity($request, 'hapus_keuangan', 'Menghapus transaksi keuangan '.$info);

            return response()->json(['ok' => true, 'message' => 'Transaksi dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus keuangan', ['id' => $keuangan->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus transaksi. Coba lagi.'], 500);
        }
    }
}
