<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Kandang;
use App\Models\Keuangan;
use App\Models\Penjualan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Menu Baby (loloh): burung fase 'anakan' tanpa kandang, siap dijual atau diloloh.
 * Baby bisa dipindah ke kandang/aviary (endpoint admin.burung.* yang sama) —
 * setelah ditempatkan, baby tampil sebagai chip di papan kandang/aviary.
 */
class BabyController extends Controller
{
    use LogsActivity;

    public function index()
    {
        // Baby = anakan tanpa kandang (belum ditempatkan). Ditempatkan → pindah domain ke papan kandang/aviary.
        $babies = Burung::where('fase', 'anakan')->whereNull('kandang_id')->orderByDesc('created_at')->get();

        // Target drag & drop ringkas (satu halaman dengan baby): kandang per blok + aviary.
        $kandangs = Kandang::with('burungs:id,nama,spesies,fase,gender,nomor_ring,kandang_id')
            ->where('tipe', '!=', 'aviary')->orderBy('blok')->orderBy('nomor')->get()->groupBy('blok');
        $aviaries = Kandang::with('burungs:id,nama,spesies,fase,gender,nomor_ring,kandang_id')
            ->where('tipe', 'aviary')->orderBy('blok')->orderBy('nomor')->get();

        // Dropdown jual: semua burung beserta lokasinya (dewasa wajib dikeluarkan dari kandang dulu).
        $burungSemua = Burung::with('kandang:id,blok,nomor,tipe')->orderBy('nama')->get();

        // Riwayat penjualan + filter periode (server-side).
        $now = now('Asia/Jakarta');
        $penjualans = Penjualan::orderByDesc('tanggal_terjual')->orderByDesc('id');
        $filter = request('filter');
        if ($filter === 'minggu') {
            $penjualans->whereBetween('tanggal_terjual', [$now->copy()->startOfWeek()->toDateString(), $now->copy()->endOfWeek()->toDateString()]);
        } elseif ($filter === '30') {
            $penjualans->where('tanggal_terjual', '>=', $now->copy()->subDays(30)->toDateString());
        } elseif ($filter === 'bulan') {
            $penjualans->whereMonth('tanggal_terjual', $now->month)->whereYear('tanggal_terjual', $now->year);
        } elseif ($filter === 'tahun') {
            $penjualans->whereYear('tanggal_terjual', $now->year);
        } elseif ($filter === 'rentang') {
            $penjualans->where('tanggal_terjual', '>=', request('dari') ?: '1900-01-01')
                ->where('tanggal_terjual', '<=', request('sampai') ?: now('Asia/Jakarta')->toDateString());
        }
        $penjualans = $penjualans->get();

        return view('admin.baby.index', compact('babies', 'kandangs', 'aviaries', 'burungSemua', 'penjualans', 'filter'));
    }

    public function update(Request $request, Burung $burung)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'spesies' => ['required', 'string', 'max:100'],
            'fase' => ['required', 'in:anakan,dewasa'],
            'gender' => ['nullable', 'in:jantan,betina'],
            'nomor_ring' => ['nullable', 'string', 'max:50'],
            'nomor_dna' => ['nullable', 'string', 'max:50'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $burung->update($data);
            Cache::forget('dashboard.stats');
            $this->logActivity($request, 'update_baby', 'Memperbarui data baby '.$burung->nama);

            return response()->json(['ok' => true, 'message' => 'Data baby "'.$burung->nama.'" diperbarui.']);
        } catch (\Throwable $e) {
            Log::error('Gagal update baby', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal memperbarui data baby. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, Burung $burung)
    {
        try {
            $nama = $burung->nama;
            $burung->delete();
            Cache::forget('dashboard.stats');
            $this->logActivity($request, 'hapus_baby', 'Menghapus baby '.$nama);

            return response()->json(['ok' => true, 'message' => 'Baby "'.$nama.'" dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus baby', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus baby. Coba lagi.'], 500);
        }
    }

    public function jual(Request $request)
    {
        $data = $request->validate([
            'burung_id' => ['required', 'integer', 'exists:burungs,id'],
            'tanggal_terjual' => ['required', 'date', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'harga' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        $burung = Burung::find($data['burung_id']);

        // Indukan/baby yang masih ditempatkan wajib dikeluarkan dulu (toast peringatan).
        if ($burung->kandang_id) {
            return response()->json([
                'ok' => false,
                'message' => $burung->nama.' masih di '.$burung->kandang->label.'. Keluarkan dari kandang/aviary dulu sebelum dijual.',
            ], 422);
        }

        try {
            $hasil = DB::transaction(function () use ($request, $burung, $data) {
                // Uang penjualan otomatis masuk Kas Umum (financial_tab_id NULL).
                $keuangan = Keuangan::create([
                    'tipe' => 'masuk',
                    'kategori' => 'Penjualan',
                    'financial_tab_id' => null,
                    'jumlah' => $data['harga'],
                    'tanggal' => $data['tanggal_terjual'],
                    'keterangan' => 'Penjualan burung '.$burung->nama.($burung->nomor_ring ? ' (ring '.$burung->nomor_ring.')' : ''),
                ]);

                // Snapshot penjualan: data burung tetap tersimpan walau baris burung dihapus.
                $penjualan = Penjualan::create([
                    'burung_id' => $burung->id,
                    'nama_burung' => $burung->nama,
                    'nomor_ring' => $burung->nomor_ring,
                    'induk_label' => $burung->induk_label,
                    'usia_hari' => $burung->usia_hari,
                    'harga' => $data['harga'],
                    'tanggal_terjual' => $data['tanggal_terjual'],
                    'keuangan_id' => $keuangan->id,
                    'keterangan' => $data['keterangan'] ?? null,
                ]);

                $burung->delete();

                return $penjualan;
            });

            Cache::forget('dashboard.stats');
            $this->logActivity($request, 'jual_burung', 'Menjual burung '.$hasil->nama_burung.' (Rp '.number_format((float) $hasil->harga, 0, ',', '.').', uang masuk Kas Umum)');

            return response()->json(['ok' => true, 'message' => $hasil->nama_burung.' terjual — uang masuk Kas Umum.']);
        } catch (\Throwable $e) {
            Log::error('Gagal jual burung', ['id' => $data['burung_id'], 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat penjualan. Coba lagi.'], 500);
        }
    }
}
