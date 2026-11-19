<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FinancialTab;
use App\Models\FinancialTabCost;
use App\Models\Keuangan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
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
            'jumlahTransaksi' => $tab?->keuangans()->count() ?? 0,
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
            if ($keuangan->transfer_id) {
                // Transfer = pasangan dua sisi; hapus kedua sisinya sekaligus
                // agar buku asal & tujuan tetap cocok.
                Keuangan::where('transfer_id', $keuangan->transfer_id)->delete();
                $info .= ' (pasangan transfer)';
            } else {
                $keuangan->delete();
            }
            $this->logActivity($request, 'hapus_keuangan', 'Menghapus transaksi keuangan '.$info);

            return response()->json(['ok' => true, 'message' => 'Transaksi dihapus.'.($keuangan->transfer_id ? ' Sisi pasangannya pada tab tujuan/asal ikut terhapus.' : '')]);
        } catch (\Throwable $e) {
            Log::error('Gagal hapus keuangan', ['id' => $keuangan->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal menghapus transaksi. Coba lagi.'], 500);
        }
    }

    public function transfer(Request $request)
    {
        $data = $request->validate([
            'dari' => ['required'],
            'ke' => ['required', 'integer', 'exists:financial_tabs,id'],
            'nominal' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        // Asal: "kas_umum" (virtual, financial_tab_id NULL) atau id tab proyek.
        $dariTab = ctype_digit((string) $data['dari']) && (int) $data['dari'] > 0
            ? FinancialTab::findOrFail((int) $data['dari'])
            : null;

        if ($dariTab && (int) $dariTab->id === (int) $data['ke']) {
            return response()->json(['ok' => false, 'message' => 'Tab asal dan tujuan tidak boleh sama.'], 422);
        }

        // Saldo asal dihitung dari DATABASE, bukan dari input.
        $saldoAsal = Keuangan::query()
            ->when($dariTab, fn ($q) => $q->where('financial_tab_id', $dariTab->id), fn ($q) => $q->whereNull('financial_tab_id'))
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS saldo")
            ->value('saldo');

        if ((float) $saldoAsal < (float) $data['nominal']) {
            return response()->json(['ok' => false, 'message' => 'Saldo asal tidak cukup (saldo: Rp '.number_format((float) $saldoAsal, 0, ',', '.').').'], 422);
        }

        try {
            $transferId = Str::uuid()->toString();
            $hariIni = now('Asia/Jakarta')->toDateString();
            $namaAsal = $dariTab ? $dariTab->nama : 'Kas Umum';
            $tujuan = FinancialTab::findOrFail($data['ke']);
            $catatan = $data['keterangan'] ?? null;

            Keuangan::create([
                'tipe' => 'keluar', 'kategori' => 'Transfer',
                'financial_tab_id' => $dariTab?->id, 'transfer_id' => $transferId,
                'jumlah' => $data['nominal'], 'tanggal' => $hariIni,
                'keterangan' => 'Transfer ke '.$tujuan->nama.($catatan ? ' — '.$catatan : ''),
            ]);
            Keuangan::create([
                'tipe' => 'masuk', 'kategori' => 'Transfer',
                'financial_tab_id' => $tujuan->id, 'transfer_id' => $transferId,
                'jumlah' => $data['nominal'], 'tanggal' => $hariIni,
                'keterangan' => 'Dari '.$namaAsal.($catatan ? ' — '.$catatan : ''),
            ]);

            $this->logActivity($request, 'transfer_dana', 'Transfer dana Rp '.number_format((float) $data['nominal'], 0, ',', '.').' dari '.$namaAsal.' ke '.$tujuan->nama);

            return response()->json(['ok' => true, 'transfer_id' => $transferId, 'message' => 'Transfer Rp '.number_format((float) $data['nominal'], 0, ',', '.').' dari '.$namaAsal.' ke '.$tujuan->nama.' dicatat.']);
        } catch (\Throwable $e) {
            Log::error('Gagal transfer dana', ['error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal mencatat transfer. Coba lagi.'], 500);
        }
    }

    public function update(Request $request, Keuangan $keuangan)
    {
        // Baris transfer = pasangan dua sisi: mengubah satu sisi membuat buku
        // timpang — cukup Hapus (menghapus pasangan) atau transfer ulang.
        if ($keuangan->transfer_id) {
            return response()->json(['ok' => false, 'message' => 'Baris transfer tidak bisa diedit sendirian. Hapus pasangannya lalu transfer ulang.'], 422);
        }

        // Batas "hari ini" pakai WIB eksplisit (produksi UTC).
        $hariIni = now('Asia/Jakarta')->toDateString();

        $data = $request->validate([
            'tipe' => ['required', 'in:masuk,keluar'],
            'kategori' => ['required', 'in:'.implode(',', Keuangan::KATEGORI)],
            'jumlah' => ['required', 'numeric', 'min:1', 'max:999999999999.99'],
            'tanggal' => ['required', 'date', 'before_or_equal:'.$hariIni],
            'keterangan' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $lama = $keuangan->jumlah_format.' ('.$keuangan->kategori.')';
            $keuangan->update($data);
            $this->logActivity($request, 'edit_keuangan', 'Mengubah transaksi keuangan '.$lama.' menjadi '.$keuangan->jumlah_format.' ('.$keuangan->kategori.')');

            return response()->json(['ok' => true, 'message' => 'Transaksi diperbarui.']);
        } catch (\Throwable $e) {
            Log::error('Gagal edit keuangan', ['id' => $keuangan->id, 'error' => $e->getMessage()]);

            return response()->json(['ok' => false, 'message' => 'Gagal memperbarui transaksi. Coba lagi.'], 500);
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
        // Uang tidak pernah berpindah diam-diam: tab yang masih berisi
        // transaksi TIDAK boleh dihapus — kecuali admin memilih hapus massal
        // (?hapus_semua=1): seluruh transaksi tab ikut terhapus bersama tabnya.
        $jumlahTransaksi = $tab->keuangans()->count();

        if ($jumlahTransaksi > 0 && ! $request->boolean('hapus_semua')) {
            return response()->json([
                'ok' => false,
                'message' => "Tab \"{$tab->nama}\" masih memiliki {$jumlahTransaksi} transaksi. Hapus satu-satu, atau pilih \"Hapus Semua + Tab\".",
            ], 422);
        }

        try {
            DB::transaction(function () use ($tab, $jumlahTransaksi) {
                if ($jumlahTransaksi > 0) {
                    $tab->keuangans()->delete();
                }
                // Rincian biaya ikut terhapus (FK cascade)
                $tab->delete();
            });
            $this->logActivity($request, 'hapus_tab_keuangan', 'Menghapus tab keuangan "'.$tab->nama.'"'.($jumlahTransaksi > 0 ? ' beserta '.$jumlahTransaksi.' transaksinya' : ''));

            return response()->json(['ok' => true, 'message' => 'Tab "'.$tab->nama.'" dihapus.'.($jumlahTransaksi > 0 ? ' Beserta '.$jumlahTransaksi.' transaksinya.' : '')]);
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
