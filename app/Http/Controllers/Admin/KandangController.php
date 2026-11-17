<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LogsActivity;
use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Kandang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        // Eager loading: tanpa N+1 (1 query kandang + 1 query burung).
        // Aviary (tipe 'aviary') dipisah ke halaman Aviary, tidak di papan ini.
        $kandangs = Kandang::with('burungs')
            ->where('tipe', '!=', 'aviary')
            ->orderBy('blok')
            ->orderBy('nomor')
            ->get()
            ->groupBy('blok');

        // Pool = hanya burung bebas. Burung aviary tidak ditawarkan di sini —
        // kandang & aviary saling eksklusif: keluarkan dari aviary dulu.
        $pool = Burung::with('kandang:id,blok,nomor,tipe')
            ->whereNull('kandang_id')
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
            // Kapasitas kandang biasa = 2 burung; Aviary tanpa batas (pra-breeding).
            // lockForUpdate mencegah dua admin menempatkan burung ke kandang
            // terakhir slotnya secara serentak.
            $hasil = DB::transaction(function () use ($request, $burung, $data) {
                $kandang = Kandang::lockForUpdate()->findOrFail($data['kandang_id']);

                // Aturan bos: kandang breeding dan Aviary saling eksklusif —
                // pindah zona harus lewat pool bebas (keluarkan dulu).
                if ($kandang->tipe === 'aviary' && $burung->kandang_id && $burung->kandang->tipe !== 'aviary') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Burung masih di kandang breeding '.$burung->kandang->label.'. Keluarkan dari kandang dulu sebelum dipindah ke Aviary.',
                    ], 422);
                }

                if ($kandang->tipe !== 'aviary' && $burung->kandang_id && $burung->kandang->tipe === 'aviary') {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Burung masih di '.$burung->kandang->label.'. Keluarkan dari Aviary dulu sebelum dipindah ke kandang.',
                    ], 422);
                }

                $isi = $kandang->burungs()->where('id', '!=', $burung->id)->count();

                if ($kandang->tipe !== 'aviary' && $isi >= 2) {
                    return response()->json([
                        'ok' => false,
                        'message' => 'Kandang '.$kandang->label.' sudah penuh (maksimal 2 burung).',
                    ], 422);
                }

                $burung->update(['kandang_id' => $kandang->id]);
                $this->logActivity($request, 'letakkan_burung', 'Menempatkan '.$burung->nama.' ke '.$kandang->label);

                return response()->json(['ok' => true, 'message' => $burung->nama.' ditempatkan di '.$kandang->label]);
            });

            return $hasil;
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json(['ok' => false, 'message' => 'Kandang tidak ditemukan.'], 422);
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

    public function updateBurung(Request $request, Burung $burung)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'spesies' => ['required', 'string', 'max:100'],
            'fase' => ['required', 'in:anakan,dewasa'],
            'gender' => ['nullable', 'in:jantan,betina'],
            'nomor_dna' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $burung->update($data);
            $this->logActivity($request, 'update_burung', 'Memperbarui data burung '.$burung->nama);

            return response()->json(['ok' => true, 'message' => 'Data burung "'.$burung->nama.'" diperbarui.']);
        } catch (\Throwable $e) {
            Log::error('Gagal update burung', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal memperbarui data burung. Coba lagi.'], 500);
        }
    }
}
