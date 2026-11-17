<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LogsActivity;
use App\Http\Controllers\Controller;
use App\Models\StokMakanan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Stok makanan burung (biji-bijian & pellet) + prediksi kapan harus beli lagi.
 */
class StokMakananController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $stoks = StokMakanan::orderBy('nama')->get();
        $perluBeli = $stoks->filter(fn ($s) => $s->perlu_beli);

        return view('admin.stok-makanan.index', compact('stoks', 'perluBeli'));
    }

    public function exportPdf(Request $request)
    {
        try {
            $stoks = StokMakanan::orderBy('nama')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.stok-makanan.pdf', compact('stoks'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_stok_pdf', 'Mengekspor laporan stok makanan PDF ('.$stoks->count().' item)');

            return $pdf->download('laporan_stok_makanan_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF stok makanan', ['error' => $e->getMessage()]);

            return redirect()->route('admin.stok.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'jumlah_stok' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'satuan' => ['required', 'in:kg,gr,ons,sak'],
            'konsumsi_per_hari' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            StokMakanan::create($data);
            $this->logActivity($request, 'tambah_stok', 'Menambah stok makanan '.$data['nama'].' '.$data['jumlah_stok'].' '.$data['satuan']);

            return response()->json(['ok' => true, 'message' => 'Stok "'.$data['nama'].'" dicatat.']);
        } catch (\Throwable $e) {
            Log::error('Gagal catat stok makanan', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat stok. Coba lagi.'], 500);
        }
    }

    public function update(Request $request, StokMakanan $stokMakanan)
    {
        $data = $request->validate([
            'jumlah_stok' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'konsumsi_per_hari' => ['required', 'numeric', 'min:0.01', 'max:99999.99'],
            'satuan' => ['required', 'in:kg,gr,ons,sak'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $stokMakanan->update($data);
            $this->logActivity($request, 'update_stok', 'Memperbarui stok makanan '.$stokMakanan->nama);

            return response()->json(['ok' => true, 'message' => 'Stok "'.$stokMakanan->nama.'" diperbarui.']);
        } catch (\Throwable $e) {
            Log::error('Gagal update stok makanan', ['id' => $stokMakanan->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal memperbarui stok. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, StokMakanan $stokMakanan)
    {
        try {
            $nama = $stokMakanan->nama;
            $stokMakanan->delete();
            $this->logActivity($request, 'hapus_stok', 'Menghapus stok makanan '.$nama);

            return response()->json(['ok' => true, 'message' => 'Stok "'.$nama.'" dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus stok makanan', ['id' => $stokMakanan->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus stok. Coba lagi.'], 500);
        }
    }
}
