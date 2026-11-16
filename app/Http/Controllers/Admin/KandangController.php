<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LogsActivity;
use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Kandang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Kandang & penempatan burung individu (papan drag-and-drop).
 * Seluruh aksi hanya untuk admin (rute dalam grup admin.only).
 */
class KandangController extends Controller
{
    use LogsActivity;

    public function index()
    {
        // Eager loading: tanpa N+1 (1 query kandang + 1 query burung)
        $kandangs = Kandang::with('burungs')
            ->orderBy('blok')
            ->orderBy('nomor')
            ->get()
            ->groupBy('blok');

        $pool = Burung::whereNull('kandang_id')
            ->orderByDesc('created_at')
            ->get();

        $total = Burung::count();
        $terpasang = $total - $pool->count();

        return view('admin.kandang.index', compact('kandangs', 'pool', 'total', 'terpasang'));
    }

    public function storeBurung(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'spesies' => ['required', 'string', 'max:100'],
            'fase' => ['required', 'in:anakan,dewasa'],
            'gender' => ['nullable', 'in:jantan,betina'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $burung = Burung::create($data);
            $this->logActivity($request, 'tambah_burung', 'Menambah burung '.$burung->nama);

            return response()->json([
                'ok' => true,
                'message' => 'Burung "'.$burung->nama.'" ditambahkan.',
                'burung' => [
                    'id' => $burung->id,
                    'nama' => $burung->nama,
                    'spesies' => $burung->spesies,
                    'fase' => $burung->fase,
                    'gender' => $burung->gender,
                ],
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal tambah burung', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menyimpan burung. Coba lagi.'], 500);
        }
    }

    public function destroyBurung(Request $request, Burung $burung)
    {
        try {
            $nama = $burung->nama;
            $burung->delete();
            $this->logActivity($request, 'hapus_burung', 'Menghapus burung '.$nama);

            return response()->json(['ok' => true, 'message' => 'Burung "'.$nama.'" dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus burung', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus burung. Coba lagi.'], 500);
        }
    }

    public function letakkan(Request $request, Burung $burung)
    {
        $data = $request->validate([
            'kandang_id' => ['required', 'integer', 'exists:kandangs,id'],
        ]);

        try {
            $burung->update(['kandang_id' => $data['kandang_id']]);
            $kandang = Kandang::find($data['kandang_id']);
            $this->logActivity($request, 'letakkan_burung', 'Menempatkan '.$burung->nama.' ke '.$kandang->label);

            return response()->json(['ok' => true, 'message' => $burung->nama.' ditempatkan di kandang '.$kandang->label]);
        } catch (\Throwable $e) {
            Log::error('Gagal letakkan burung', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menempatkan burung. Coba lagi.'], 500);
        }
    }

    public function kosongkan(Request $request, Burung $burung)
    {
        try {
            $burung->update(['kandang_id' => null]);
            $this->logActivity($request, 'kosongkan_burung', 'Mengeluarkan '.$burung->nama.' dari kandang');

            return response()->json(['ok' => true, 'message' => $burung->nama.' dikeluarkan dari kandang.']);
        } catch (\Throwable $e) {
            Log::error('Gagal kosongkan burung', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mengeluarkan burung. Coba lagi.'], 500);
        }
    }
}
