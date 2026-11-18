<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialTab;
use App\Models\FinancialTabCost;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

/**
 * Keuangan farm: pemasukan, pengeluaran, gaji bulanan, budget pembangunan.
 * Multi-tab: tab "Kas Umum" = virtual (transaksi financial_tab_id NULL);
 * tab proyek tersimpan di financial_tabs + rincian biaya financial_tab_costs.
 * Jumlah divalidasi server-side; seluruh aksi ter-audit via LogsActivity.
 */
class KeuanganController extends Controller
{
    use LogsActivity;

    public function index(Request $request)
    {
        $tipe = in_array($request->query('tipe'), ['masuk', 'keluar']) ? $request->query('tipe') : null;
        $kategori = in_array($request->query('kategori'), Keuangan::KATEGORI) ? $request->query('kategori') : null;

        // ?tab= kosong/absen = Kas Umum (financial_tab_id NULL); angka = tab proyek
        $tab = null;
        if (is_numeric($request->query('tab'))) {
            $tab = FinancialTab::with('costs')->findOrFail((int) $request->query('tab'));
        }

        $dasar = Keuangan::query()
            ->when($tab, fn ($q) => $q->where('financial_tab_id', $tab->id), fn ($q) => $q->whereNull('financial_tab_id'))
            ->when($tipe, fn ($q) => $q->where('tipe', $tipe))
            ->when($kategori, fn ($q) => $q->where('kategori', $kategori));

        $ringkasan = [
            'masuk' => (clone $dasar)->where('tipe', 'masuk')->sum('jumlah'),
            'keluar' => (clone $dasar)->where('tipe', 'keluar')->sum('jumlah'),
        ];
        $ringkasan['saldo'] = $ringkasan['masuk'] - $ringkasan['keluar'];

        // Khusus tab proyek: TOTAL BUDGET (target + biaya tambahan) & SISA BUDGET
        $totalBudget = null;
        $sisaBudget = null;
        if ($tab) {
            $totalBudget = $tab->totalBudget();
            $sisaBudget = $totalBudget - (float) $ringkasan['keluar'];
        }

        $transaksis = (clone $dasar)->orderByDesc('tanggal')->orderByDesc('id')->paginate(20)->withQueryString();
        // Seluruh baris sesuai filter — sumber ekspor Excel sisi browser (farm-scale, ratusan baris)
        $semuaTransaksi = (clone $dasar)->orderByDesc('tanggal')->orderByDesc('id')->get();
        $tabs = FinancialTab::orderBy('nama')->get(['id', 'nama']);

        return view('admin.keuangan.index', [
            'transaksis' => $transaksis,
            'semuaTransaksi' => $semuaTransaksi,
            'ringkasan' => $ringkasan,
            'kategoriList' => Keuangan::KATEGORI,
            'tipeAktif' => $tipe,
            'kategoriAktif' => $kategori,
            'tabs' => $tabs,
            'tabAktif' => $tab,
            'totalBudget' => $totalBudget,
            'sisaBudget' => $sisaBudget,
        ]);
    }

    public function exportPdf(Request $request)
    {
        $tipe = in_array($request->query('tipe'), ['masuk', 'keluar']) ? $request->query('tipe') : null;
        $kategori = in_array($request->query('kategori'), Keuangan::KATEGORI) ? $request->query('kategori') : null;

        $tab = null;
        if (is_numeric($request->query('tab'))) {
            $tab = FinancialTab::with('costs')->findOrFail((int) $request->query('tab'));
        }

        try {
            $query = Keuangan::query()
                ->when($tab, fn ($q) => $q->where('financial_tab_id', $tab->id), fn ($q) => $q->whereNull('financial_tab_id'))
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
            $totalBudget = $tab?->totalBudget();
            $sisaBudget = $tab ? $totalBudget - (float) $ringkasan['keluar'] : null;

            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('admin.keuangan.pdf', compact(
                'transaksis', 'ringkasan', 'tipeAktif', 'kategoriAktif', 'tab', 'totalBudget', 'sisaBudget'
            ))->setPaper('a4', 'portrait');

            $this->logActivity($request, 'ekspor_keuangan_pdf', 'Mengekspor laporan keuangan PDF ('.($tab ? 'tab '.$tab->nama.', ' : '').$transaksis->count().' transaksi'.($tipe ? ', tipe '.$tipe : '').($kategori ? ', kategori '.$kategori : '').')');

            $nama = $tab ? 'laporan_'.$this->slug($tab->nama).'_' : 'laporan_keuangan_';

            return $pdf->download($nama.date('Y-m-d').'.pdf');
        } catch (\Throwable $e) {
            Log::error('Gagal ekspor PDF keuangan', ['error' => $e->getMessage()]);

            return redirect()->route('admin.keuangan.index')->with('error', 'Gagal membuat PDF. Coba lagi.');
        }
    }

    public function store(Request $request)
    {
        // Batas "hari ini" pakai WIB eksplisit: APP_TIMEZONE=UTC membuat jam 00:00-06:59 WIB
        // masih dianggap kemarin oleh aturan before_or_equal:today bawaan.
        $hariIni = now('Asia/Jakarta')->toDateString();

        $data = $request->validate([
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori' => ['required', 'in:'.implode(',', Keuangan::KATEGORI)],
            'financial_tab_id' => ['nullable', 'integer', 'exists:financial_tabs,id'],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'tanggal' => ['required', 'date', 'before_or_equal:'.$hariIni],
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

    // ==================== TAB PROYEK ====================

    public function storeTab(Request $request)
    {
        $data = $this->validasiNamaTab($request);

        try {
            $tab = FinancialTab::create(['nama' => $data['nama']]);
            $this->logActivity($request, 'tambah_tab_keuangan', 'Membuat tab keuangan proyek "'.$tab->nama.'"');

            return response()->json(['ok' => true, 'id' => $tab->id, 'message' => 'Tab "'.$tab->nama.'" dibuat.']);
        } catch (\Throwable $e) {
            Log::error('Gagal buat tab keuangan', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal membuat tab. Coba lagi.'], 500);
        }
    }

    public function updateTab(Request $request, FinancialTab $tab)
    {
        $data = $this->validasiNamaTab($request, $tab->id);

        try {
            $lama = $tab->nama;
            $tab->update(['nama' => $data['nama']]);
            $this->logActivity($request, 'rename_tab_keuangan', 'Mengganti nama tab keuangan "'.$lama.'" menjadi "'.$tab->nama.'"');

            return response()->json(['ok' => true, 'message' => 'Nama tab diubah.']);
        } catch (\Throwable $e) {
            Log::error('Gagal rename tab keuangan', ['id' => $tab->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mengubah nama tab. Coba lagi.'], 500);
        }
    }

    public function destroyTab(Request $request, FinancialTab $tab)
    {
        try {
            DB::transaction(function () use ($tab) {
                // Transaksi tidak hilang: pindah ke Kas Umum (financial_tab_id NULL);
                // rincian biaya ikut terhapus (FK cascade).
                $tab->keuangans()->update(['financial_tab_id' => null]);
                $tab->delete();
            });
            $this->logActivity($request, 'hapus_tab_keuangan', 'Menghapus tab keuangan "'.$tab->nama.'" (transaksi dipindah ke Kas Umum)');

            return response()->json(['ok' => true, 'message' => 'Tab "'.$tab->nama.'" dihapus. Transaksinya dipindahkan ke Kas Umum.']);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus tab keuangan', ['id' => $tab->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus tab. Coba lagi.'], 500);
        }
    }

    public function updateBudget(Request $request, FinancialTab $tab)
    {
        $data = $request->validate([
            'target_anggaran' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);

        try {
            $tab->update(['target_anggaran' => $data['target_anggaran']]);
            $this->logActivity($request, 'atur_budget_tab', 'Mengatur target anggaran tab "'.$tab->nama.'" menjadi '.$tab->jumlah_format);

            return response()->json([
                'ok' => true,
                'message' => 'Target anggaran disimpan.',
                'total_budget' => $tab->totalBudget(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal atur budget tab', ['id' => $tab->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menyimpan target anggaran. Coba lagi.'], 500);
        }
    }

    public function storeCost(Request $request, FinancialTab $tab)
    {
        $data = $this->validasiBiaya($request);

        try {
            $cost = $tab->costs()->create(['nama' => $data['nama'], 'nominal' => $data['nominal']]);
            $this->logActivity($request, 'tambah_biaya_tab', 'Menambah biaya "'.$cost->nama.'" ('.$cost->jumlah_format.') pada tab "'.$tab->nama.'"');

            return response()->json([
                'ok' => true,
                'id' => $cost->id,
                'message' => 'Biaya ditambahkan.',
                'total_budget' => $tab->totalBudget(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal tambah biaya tab', ['id' => $tab->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menambah biaya. Coba lagi.'], 500);
        }
    }

    public function updateCost(Request $request, FinancialTabCost $cost)
    {
        $data = $this->validasiBiaya($request);

        try {
            $cost->update(['nama' => $data['nama'], 'nominal' => $data['nominal']]);
            $this->logActivity($request, 'ubah_biaya_tab', 'Mengubah biaya "'.$cost->nama.'" ('.$cost->jumlah_format.')');

            return response()->json([
                'ok' => true,
                'message' => 'Biaya diperbarui.',
                'total_budget' => $cost->tab->totalBudget(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal ubah biaya tab', ['id' => $cost->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mengubah biaya. Coba lagi.'], 500);
        }
    }

    public function destroyCost(Request $request, FinancialTabCost $cost)
    {
        try {
            $nama = $cost->nama;
            $tab = $cost->tab;
            $cost->delete();
            $this->logActivity($request, 'hapus_biaya_tab', 'Menghapus biaya "'.$nama.'" dari tab "'.$tab->nama.'"');

            return response()->json([
                'ok' => true,
                'message' => 'Biaya dihapus.',
                'total_budget' => $tab->totalBudget(),
            ]);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus biaya tab', ['id' => $cost->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus biaya. Coba lagi.'], 500);
        }
    }

    // ==================== BANTU ====================

    private function validasiNamaTab(Request $request, ?int $abaikanId = null): array
    {
        $unik = Rule::unique('financial_tabs', 'nama');
        if ($abaikanId !== null) {
            $unik->ignore($abaikanId);
        }

        return $request->validate([
            'nama' => ['required', 'string', 'max:100', $unik],
        ]);
    }

    private function validasiBiaya(Request $request)
    {
        return $request->validate([
            'nama' => ['required', 'string', 'max:150'],
            'nominal' => ['required', 'numeric', 'min:0', 'max:999999999999.99'],
        ]);
    }

    private function slug(string $nama): string
    {
        return trim(preg_replace('/[^a-z0-9]+/', '-', strtolower($nama)), '-') ?: 'proyek';
    }
}
