<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Telur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Monitoring telur: sesi bertelur, perebut, usia harian, menetas → incubator → siap dilepas.
 * 1 baris = 1 butir: jumlah=N membuat N baris (butir_ke 1..N) — tiap butir punya
 * status & tanggal menetas sendiri sehingga menetasnya bisa berbeda per butir.
 */
class TelurController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $this->autoKonversiBaby();

        $telurs = Telur::orderByDesc('tanggal_diambil')->orderByDesc('id')->get();

        // Dropdown = kandang BIASA berisi TEPAT 2 induk dewasa (pasangan).
        // Aviary dikecualikan (pra-breeding, bukan kandang pasangan). Satu query + operasi koleksi, tanpa N+1.
        $burungDewasa = Burung::with('kandang:id,blok,nomor,tipe')
            ->where('fase', 'dewasa')
            ->whereNotNull('kandang_id')
            ->orderBy('nama')
            ->get(['id', 'nama', 'spesies', 'gender', 'kandang_id']);

        $kandangPasangan = $burungDewasa
            ->groupBy('kandang_id')
            ->filter(fn ($grup) => $grup->count() === 2 && $grup->first()->kandang->tipe !== 'aviary')
            ->map(fn ($grup) => [
                'label' => $grup->first()->kandang->label,
                'burung' => $grup->map(fn ($b) => ['id' => $b->id, 'nama' => $b->nama, 'spesies' => $b->spesies, 'gender' => $b->gender_label])->values()->all(),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        // Relasi induk telur dari koleksi yang sudah dimuat (hemat 2 round-trip TiDB);
        // ID yang tak ada di koleksi (bukan dewasa / tanpa kandang) di-fetch 1 query.
        $perlu = $telurs->flatMap(fn ($t) => [$t->burung_id, $t->burung_pasangan_id])
            ->filter()->unique()->diff($burungDewasa->pluck('id'));
        $ekstra = $perlu->isEmpty() ? collect() : Burung::with('kandang:id,blok,nomor,tipe')->findMany($perlu)->keyBy('id');
        $indeks = $burungDewasa->keyBy('id')->union($ekstra);
        $telurs->each(function ($t) use ($indeks) {
            $t->setRelation('burung', $indeks[$t->burung_id] ?? null);
            $t->setRelation('pasangan', $indeks[$t->burung_pasangan_id] ?? null);
        });

        return view('admin.telur.index', compact('telurs', 'kandangPasangan'));
    }

    /** Baby ≥1 bulan muncul otomatis: butir menetas yang belum dikonversi manual dibuatkan baby bawaan. */
    private function autoKonversiBaby(): void
    {
        $siap = Telur::with('burung.kandang', 'pasangan')
            ->where('status', 'menetas')
            ->whereNotNull('tanggal_menetas')
            ->whereDate('tanggal_menetas', '<=', now('Asia/Jakarta')->subDays(30)->toDateString())
            ->whereDoesntHave('baby')
            ->get();

        foreach ($siap as $telur) {
            try {
                $telur->load('burung.kandang');
                DB::transaction(function () use ($telur) {
                    $kandang = $telur->burung?->kandang;
                    Burung::create([
                        'nama' => 'Baby '.($telur->burung?->spesies ?? 'burung').' '.($kandang?->label ?? '?').' b'.$telur->butir_ke,
                        'spesies' => $telur->burung?->spesies ?? 'Belum diidentifikasi',
                        'fase' => 'anakan',
                        'telur_id' => $telur->id,
                        'tanggal_lahir' => $telur->tanggal_menetas,
                        'induk_label' => $this->silsilahLabel($telur),
                        'asal_label' => $this->asalLabel($telur),
                        'catatan' => 'Diloloh otomatis usia 1 bulan — nama & ring bisa diedit di menu Baby.',
                    ]);
                    $telur->update(['status' => 'dilepas']);
                });
            } catch (\Illuminate\Database\QueryException $e) {
                // unique telur_id: burung sudah dibuat oleh request lain → lewati butir ini
                continue;
            }
        }
        if ($siap->isNotEmpty()) {
            Cache::forget('dashboard.stats');
        }
    }

    public function exportPdf(Request $request)
    {
        try {
            $telurs = Telur::with(['burung.kandang', 'pasangan.kandang'])->orderByDesc('tanggal_diambil')->orderByDesc('id')->get();

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.telur.pdf', compact('telurs'))
                ->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_telur_pdf', 'Mengekspor laporan telur & incubator PDF ('.$telurs->count().' catatan)');

            return $pdf->download('laporan_telur_incubator_'.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF telur', ['error' => $e->getMessage()]);

            return redirect()->route('admin.telur.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'burung_id' => ['required', 'integer', 'exists:burungs,id'],
            'burung_pasangan_id' => ['required', 'integer', 'exists:burungs,id', 'different:burung_id'],
            'sesi' => ['required', 'integer', 'min:1', 'max:20'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:20'],
            'tanggal_diambil' => ['required', 'date', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'diambil_oleh' => ['required', 'string', 'max:100'],
            'kondisi' => ['required', 'string', 'max:200'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        // Anti-manipulasi payload: pasangan wajib 2 burung dewasa dalam SATU kandang
        // (komparasi ID pakai loose != karena PDO stringify fetches TiDB).
        $induk = Burung::whereIn('id', [$data['burung_id'], $data['burung_pasangan_id']])->get(['id', 'fase', 'kandang_id']);
        if ($induk->count() < 2
            || $induk->contains(fn ($b) => $b->fase !== 'dewasa')
            || $induk[0]->kandang_id === null
            || $induk[0]->kandang_id != $induk[1]->kandang_id) {
            return response()->json(['ok' => false, 'message' => 'Pasangan induk harus 2 burung dewasa dalam satu kandang.'], 422);
        }

        try {
            // 1 baris = 1 butir: jumlah=N membuat N baris terpisah (butir_ke 1..N),
            // tanggal diambil sama, tapi tiap butir punya status & tanggal menetas sendiri.
            $now = now();
            $rows = [];
            for ($i = 1; $i <= (int) $data['jumlah']; $i++) {
                $rows[] = array_merge($data, ['butir_ke' => $i, 'created_at' => $now, 'updated_at' => $now]);
            }
            Telur::insert($rows);
            $this->logActivity($request, 'tambah_telur', 'Mencatat telur '.$data['jumlah'].' butir dari pasangan #'.$data['burung_id'].' × #'.$data['burung_pasangan_id'].' (sesi '.$data['sesi'].', diambil oleh '.$data['diambil_oleh'].')');

            return response()->json(['ok' => true, 'message' => 'Telur sesi '.$data['sesi'].' ('.$data['jumlah'].' butir) dicatat — tiap butir termonitor sendiri.']);
        } catch (\Throwable $e) {
            Log::error('Gagal catat telur', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat telur. Coba lagi.'], 500);
        }
    }

    public function update(Request $request, Telur $telur)
    {
        $data = $request->validate([
            'kondisi' => ['required', 'string', 'max:200'],
            'status' => ['required', 'in:diinkubasi,menetas,dilepas'],
            'tanggal_menetas' => ['nullable', 'required_if:status,menetas', 'date', 'before_or_equal:'.now('Asia/Jakarta')->toDateString()],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            // Butir menetas yang dilepas WAJIB lewat konversi ke Baby (dibuat otomatis di menu Baby) —
            // mencegah status dilepas tanpa jejak baby.
            if ($data['status'] === 'dilepas' && $telur->status === 'menetas' && $telur->tanggal_menetas) {
                return response()->json(['ok' => false, 'message' => 'Butir menetas harus dikonversi ke Baby dulu — isi data baby di form ini.'], 422);
            }

            $telur->update([
                'kondisi' => $data['kondisi'],
                'status' => $data['status'],
                'tanggal_menetas' => $data['status'] === 'menetas' ? $data['tanggal_menetas'] : ($data['status'] === 'dilepas' ? $telur->tanggal_menetas : null),
                'catatan' => $data['catatan'] ?? $telur->catatan,
            ]);
            $this->logActivity($request, 'update_telur', 'Memperbarui telur #'.$telur->id.' (status '.$data['status'].')');

            return response()->json(['ok' => true, 'message' => 'Data telur diperbarui.']);
        } catch (\Throwable $e) {
            Log::error('Gagal update telur', ['id' => $telur->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal memperbarui data telur. Coba lagi.'], 500);
        }
    }

    /** Silsilah lengkap: nama + spesies + gender + ring tiap induk, "X (..) × Y (..)". */
    private function silsilahLabel(Telur $telur): string
    {
        $sisi = fn ($b) => $b ? $b->nama.' ('.$b->spesies.($b->gender_label ? ' - '.$b->gender_label : '').($b->nomor_ring ? ', Ring '.$b->nomor_ring : '').')' : '?';
        $induk = $sisi($telur->burung);
        $pasangan = $sisi($telur->pasangan);

        return $telur->pasangan ? $induk.' × '.$pasangan : (string) $induk;
    }

    /** Asal kandang induk: "Blok A · Kandang A-1". */
    private function asalLabel(Telur $telur): ?string
    {
        $kandang = $telur->burung?->kandang;

        return $kandang ? 'Blok '.$kandang->blok.' · Kandang '.$kandang->label : null;
    }

    /** Konversi butir menetas → baby di menu Baby (ring/gender/DNA opsional, diedit belakangan). */
    public function keBaby(Request $request, Telur $telur)
    {
        $data = $request->validate([
            'baby_nama' => ['required', 'string', 'max:100'],
            'baby_ring' => ['nullable', 'string', 'max:50'],
            'baby_gender' => ['nullable', 'in:jantan,betina'],
            'baby_dna' => ['nullable', 'string', 'max:50'],
            'baby_catatan' => ['nullable', 'string', 'max:500'],
        ]);

        if ($telur->status !== 'menetas' || ! $telur->tanggal_menetas) {
            return response()->json(['ok' => false, 'message' => 'Hanya butir berstatus menetas yang bisa dikonversi ke baby.'], 422);
        }
        if ($telur->baby) {
            return response()->json(['ok' => false, 'message' => 'Butir ini sudah dikonversi ke baby.'], 422);
        }

        try {
            $telur->load('burung.kandang');
            DB::transaction(function () use ($telur, $data) {
                Burung::create([
                    'nama' => $data['baby_nama'],
                    'spesies' => $telur->burung?->spesies ?? 'Belum diidentifikasi',
                    'fase' => 'anakan',
                    'gender' => $data['baby_gender'] ?? null,
                    'nomor_ring' => $data['baby_ring'] ?? null,
                    'nomor_dna' => $data['baby_dna'] ?? null,
                    'catatan' => $data['baby_catatan'] ?? null,
                    'telur_id' => $telur->id,
                    'tanggal_lahir' => $telur->tanggal_menetas,
                    'induk_label' => $this->silsilahLabel($telur),
                    'asal_label' => $this->asalLabel($telur),
                ]);
                $telur->update(['status' => 'dilepas']);
                Cache::forget('dashboard.stats');
            });
            $this->logActivity($request, 'konversi_baby', 'Konversi telur #'.$telur->id.' ('.$telur->induk_label.') menjadi baby "'.$data['baby_nama'].'"'.($data['baby_ring'] ? ' (ring '.$data['baby_ring'].')' : ''));

            return response()->json(['ok' => true, 'message' => 'Baby "'.$data['baby_nama'].'" dibuat — cek menu Baby.']);
        } catch (\Throwable $e) {
            Log::error('Gagal konversi telur ke baby', ['id' => $telur->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mengonversi telur ke baby. Coba lagi.'], 500);
        }
    }

    public function destroy(Request $request, Telur $telur)
    {
        try {
            $telur->delete();
            $this->logActivity($request, 'hapus_telur', 'Menghapus catatan telur #'.$telur->id);

            return response()->json(['ok' => true, 'message' => 'Catatan telur dihapus.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus telur', ['id' => $telur->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus catatan telur. Coba lagi.'], 500);
        }
    }
}
