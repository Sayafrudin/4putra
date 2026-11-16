<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\LogsActivity;
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
        $telurs = Telur::with('burung')->orderByDesc('tanggal_diambil')->orderByDesc('id')->get();
        $burungList = Burung::with('kandang:id,blok,nomor')->where('fase', 'dewasa')->orderBy('nama')->get(['id', 'nama', 'spesies', 'kandang_id']);

        return view('admin.telur.index', compact('telurs', 'burungList'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'burung_id' => ['required', 'integer', 'exists:burungs,id'],
            'sesi' => ['required', 'integer', 'min:1', 'max:20'],
            'jumlah' => ['required', 'integer', 'min:1', 'max:20'],
            'tanggal_diambil' => ['required', 'date', 'before_or_equal:today'],
            'diambil_oleh' => ['required', 'string', 'max:100'],
            'kondisi' => ['required', 'string', 'max:200'],
            'catatan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $telur = Telur::create($data);
            $this->logActivity($request, 'tambah_telur', 'Mencatat telur '.$data['jumlah'].' butir dari burung #'.$data['burung_id'].' (sesi '.$data['sesi'].', diambil oleh '.$data['diambil_oleh'].')');

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
            'tanggal_menetas' => ['nullable', 'required_if:status,menetas', 'date', 'before_or_equal:today'],
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
