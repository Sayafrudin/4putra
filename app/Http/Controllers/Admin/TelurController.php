<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Telur;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Monitoring telur: sesi bertelur, perebut, usia harian, menetas → incubator → siap dilepas.
 */
class TelurController extends Controller
{
    use LogsActivity;

    public function index()
    {
        $telurs = Telur::with(['burung', 'pasangan'])->orderByDesc('tanggal_diambil')->orderByDesc('id')->get();

        // Dropdown = kandang BIASA berisi TEPAT 2 induk dewasa (pasangan).
        // Aviary dikecualikan (pra-breeding, bukan kandang pasangan). Satu query + operasi koleksi, tanpa N+1.
        $kandangPasangan = Burung::with('kandang:id,blok,nomor,tipe')
            ->where('fase', 'dewasa')
            ->whereNotNull('kandang_id')
            ->orderBy('nama')
            ->get(['id', 'nama', 'spesies', 'gender', 'kandang_id'])
            ->groupBy('kandang_id')
            ->filter(fn ($grup) => $grup->count() === 2 && $grup->first()->kandang->tipe !== 'aviary')
            ->map(fn ($grup) => [
                'label' => $grup->first()->kandang->label,
                'burung' => $grup->map(fn ($b) => ['id' => $b->id, 'nama' => $b->nama, 'spesies' => $b->spesies, 'gender' => $b->gender_label])->values()->all(),
            ])
            ->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)
            ->values();

        return view('admin.telur.index', compact('telurs', 'kandangPasangan'));
    }

    public function exportPdf(Request $request)
    {
        try {
            $telurs = Telur::with(['burung', 'pasangan'])->orderByDesc('tanggal_diambil')->orderByDesc('id')->get();

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
            $telur = Telur::create($data);
            $this->logActivity($request, 'tambah_telur', 'Mencatat telur '.$data['jumlah'].' butir dari pasangan #'.$data['burung_id'].' × #'.$data['burung_pasangan_id'].' (sesi '.$data['sesi'].', diambil oleh '.$data['diambil_oleh'].')');

            return response()->json(['ok' => true, 'message' => 'Telur sesi '.$data['sesi'].' dicatat.']);
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
