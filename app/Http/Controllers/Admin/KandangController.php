<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LogsActivity;
use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\BurungPindah;
use App\Models\Kandang;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Kandang & penempatan burung individu (papan drag-and-drop).
 * Papan ada dua: farm baru (default) dan farm lama (lokasi='lama') —
 * pool burung tetap satu, kandang dibedakan kolom lokasi.
 * Seluruh aksi hanya untuk admin (rute dalam grup admin.only).
 */
class KandangController extends Controller
{
    use LogsActivity;

    public function index()
    {
        return $this->papan('baru');
    }

    public function indexLama()
    {
        return $this->papan('lama');
    }

    private function papan(string $lokasi)
    {
        // Eager loading: tanpa N+1 (1 query kandang + 1 query burung).
        // Aviary (tipe 'aviary') dipisah ke halaman Aviary, tidak di papan ini.
        $kandangs = Kandang::with('burungs')
            ->where('tipe', '!=', 'aviary')
            ->where('lokasi', $lokasi)
            ->orderBy('blok')
            ->orderBy('nomor')
            ->get()
            ->groupBy('blok');

        // Pool = hanya burung bebas yang BUKAN baby (fase anakan) — baby punya domain
        // sendiri di menu Baby. Burung aviary tidak ditawarkan di sini —
        // kandang & aviary saling eksklusif: keluarkan dari aviary dulu.
        $pool = Burung::with('kandang:id,blok,nomor,tipe')
            ->whereNull('kandang_id')
            ->where('fase', '!=', 'anakan')
            ->orderByDesc('created_at')
            ->get();

        $total = Burung::count();
        $terpasang = $total - $pool->count();

        return view('admin.kandang.index', compact(
            'kandangs', 'pool', 'total', 'terpasang', 'lokasi',
        ) + ['dataEkspor' => $this->barisEkspor($kandangs, $pool)]);
    }

    /** Baris export Excel: satu baris per burung + kandang kosong + pool. */
    private function barisEkspor($kandangs, $pool)
    {
        $baris = [];
        foreach ($kandangs as $blok => $kandangBlok) {
            foreach ($kandangBlok as $k) {
                if ($k->burungs->isEmpty()) {
                    $baris[] = [$blok, $k->label, '(kosong)', '', '', '', '', '', ''];
                    continue;
                }
                foreach ($k->burungs as $b) {
                    $baris[] = [$blok, $k->label, $b->nama, $b->spesies, $b->fase_label, $b->gender_label ?: '-', $b->nomor_ring ?: '-', $b->nomor_dna ?: '-', $b->catatan ?: '-'];
                }
            }
        }
        foreach ($pool as $b) {
            $baris[] = ['-', 'Belum Ditempatkan', $b->nama, $b->spesies, $b->fase_label, $b->gender_label ?: '-', $b->nomor_ring ?: '-', $b->nomor_dna ?: '-', $b->catatan ?: '-'];
        }

        return $baris;
    }

    public function storeKandang(Request $request)
    {
        $data = $request->validate([
            'blok' => ['required', 'string', 'max:1'],
            'nomor' => ['required', 'integer', 'min:1', 'max:9999'],
            'tipe' => ['required', 'in:kecil,medium'],
            'lokasi' => ['required', 'in:baru,lama'],
        ]);

        try {
            $kandang = Kandang::create($data);
            $this->logActivity($request, 'tambah_kandang', 'Menambah kandang '.$kandang->label.' (farm '.$kandang->lokasi.')');

            return response()->json(['ok' => true, 'message' => 'Kandang '.$kandang->label.' ditambahkan.']);
        } catch (\Illuminate\Database\QueryException $e) {
            if (($e->errorInfo[1] ?? 0) === 1062) {
                return response()->json(['ok' => false, 'message' => 'Kandang '.$data['blok'].'-'.$data['nomor'].' sudah ada.'], 422);
            }
            Log::error('Gagal tambah kandang', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menyimpan kandang. Coba lagi.'], 500);
        }
    }

    public function storeBurung(Request $request)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'spesies' => ['required', 'string', 'max:100'],
            'fase' => ['required', 'in:anakan,dewasa'],
            'gender' => ['nullable', 'in:jantan,betina'],
            'nomor_ring' => ['nullable', 'string', 'max:50'],
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
                BurungPindah::create([
                    'burung_id' => $burung->id,
                    'kandang_id' => $kandang->id,
                    'aksi' => 'ditempatkan',
                    'keterangan' => 'Ditempatkan di '.$kandang->label.' (farm '.$kandang->lokasi.')',
                ]);
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
            // Label asal diambil SEBELUM kandang_id di-null (relasi hilang setelahnya).
            $asalId = $burung->kandang_id;
            $asalLabel = $asalId ? $burung->kandang->label : null;
            $burung->update(['kandang_id' => null]);
            BurungPindah::create([
                'burung_id' => $burung->id,
                'kandang_id' => $asalId,
                'aksi' => 'dikeluarkan',
                'keterangan' => 'Dikeluarkan dari '.($asalLabel ?? 'penempatan sebelumnya'),
            ]);
            $this->logActivity($request, 'kosongkan_burung', 'Mengeluarkan '.$burung->nama.' dari kandang');

            return response()->json(['ok' => true, 'message' => $burung->nama.' dikeluarkan dari kandang.']);
        } catch (\Throwable $e) {
            Log::error('Gagal kosongkan burung', ['id' => $burung->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mengeluarkan burung. Coba lagi.'], 500);
        }
    }

    /** Riwayat perpindahan satu burung (dipakai tombol Riwayat di modal edit). */
    public function riwayatPindah(Burung $burung)
    {
        $riwayat = BurungPindah::where('burung_id', $burung->id)
            ->with('kandang:id,blok,nomor,tipe,lokasi')
            ->orderByDesc('created_at')
            ->limit(30)
            ->get()
            ->map(fn ($p) => [
                'waktu' => $p->created_at->timezone('Asia/Jakarta')->format('d M Y H:i'),
                'aksi' => $p->aksi,
                'kandang' => $p->kandang?->label,
                'farm' => $p->kandang?->lokasi,
                'keterangan' => $p->keterangan,
            ]);

        return response()->json(['ok' => true, 'riwayat' => $riwayat]);
    }

    public function updateBurung(Request $request, Burung $burung)
    {
        $data = $request->validate([
            'nama' => ['required', 'string', 'max:100'],
            'spesies' => ['required', 'string', 'max:100'],
            'fase' => ['required', 'in:anakan,dewasa'],
            'gender' => ['nullable', 'in:jantan,betina'],
            'nomor_dna' => ['nullable', 'string', 'max:50'],
            'nomor_ring' => ['nullable', 'string', 'max:50'],
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

    /** Export PDF kandang/aviary. Route membedakan: admin.aviary.export-pdf vs admin.kandang.export-pdf (+ lokasi). */
    public function exportPdf(Request $request)
    {
        $aviary = $request->route()->getName() === 'admin.aviary.export-pdf';
        $lokasi = $request->route('lokasi') ?: 'baru';

        try {
            if ($aviary) {
                $kandangs = Kandang::with('burungs')->where('tipe', 'aviary')->orderBy('nomor')->get();
                $judul = 'Laporan Aviary (Kandang Besar)';
                $namaFile = 'laporan_aviary_'.date('Y-m-d').'.pdf';
            } else {
                $kandangs = Kandang::with('burungs')
                    ->where('tipe', '!=', 'aviary')
                    ->where('lokasi', $lokasi)
                    ->orderBy('blok')
                    ->orderBy('nomor')
                    ->get();
                $judul = $lokasi === 'lama' ? 'Laporan Kandang Farm Lama' : 'Laporan Kandang & Penempatan Burung';
                $namaFile = ($lokasi === 'lama' ? 'laporan_kandang_farm_lama_' : 'laporan_kandang_').date('Y-m-d').'.pdf';
            }

            // Pool = burung bebas tanpa baby (sama dengan papan masing-masing halaman).
            $pool = Burung::whereNull('kandang_id')->where('fase', '!=', 'anakan')->orderBy('nama')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.kandang.pdf', compact('kandangs', 'pool', 'judul', 'aviary'))
                ->setPaper('a4', 'portrait');
            $this->logActivity($request, 'ekspor_kandang_pdf', 'Ekspor PDF: '.$judul);

            return $pdf->download($namaFile);
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF kandang', ['error' => $e->getMessage()]);

            return redirect()->back()->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }
}
