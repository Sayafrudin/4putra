<?php

namespace App\Support;

use App\Models\Burung;
use App\Models\DailyActivity;
use App\Models\Keuangan;
use App\Models\StokMakanan;
use App\Models\Telur;
use Illuminate\Support\Facades\Cache;

/**
 * PINTU TUNGGAL angka agregat dashboard. Semua perhitungan ringkasan farm
 * (burung, kas, stok, telur, aktivitas) hanya boleh lewat sini — hasil otomatis
 * disaring sesuai hak akses menu pemanggil, jadi halaman lain yang kelak
 * memakai angka ini tidak bisa lupa memfilter role. Agregat dibungkus cache
 * 60 detik (satu payload); titik tulis keuangan memanggil Cache::forget.
 */
class DashboardStats
{
    public static function untuk($user): array
    {
        $data = Cache::remember('dashboard.stats', 60, fn () => self::agregat());

        // Filter per hak akses: kartu/grafik yang sumbernya bukan menu pemanggil
        // tidak boleh muncul — bahkan kuncinya pun tidak dikirim ke view.
        $hasil = ['burung' => $data['burung']];

        if (AdminMenus::boleh($user, 'vaksinasi')) {
            $hasil['vaksin'] = $data['vaksin'];
        }
        if (AdminMenus::boleh($user, 'telur')) {
            $hasil['telur'] = $data['telur'];
        }
        if (AdminMenus::boleh($user, 'stok')) {
            $hasil['stok'] = $data['stok'];
        }
        if (AdminMenus::boleh($user, 'keuangan')) {
            $hasil['keuangan'] = $data['keuangan'];
            $hasil['kas30'] = $data['kas30'];
            $hasil['transaksiTerbaru'] = $data['transaksiTerbaru'];
        }
        if (AdminMenus::boleh($user, 'daily-activities')) {
            $hasil['aktivitasTerbaru'] = $data['aktivitasTerbaru'];
        }

        return $hasil;
    }

    private static function agregat(): array
    {
        $burungFase = Burung::query()->selectRaw('fase, COUNT(*) c')->groupBy('fase')->pluck('c', 'fase');
        $burungSpesies = Burung::query()->selectRaw('spesies, COUNT(*) c')->groupBy('spesies')->orderByDesc('c')->limit(6)->pluck('c', 'spesies');
        $burungLainnya = max(0, (int) array_sum($burungFase->all()) - (int) array_sum($burungSpesies->all()));

        $telurStatus = Telur::query()->selectRaw('status, COUNT(*) c')->groupBy('status')->pluck('c', 'status');

        $vaksin = \App\Models\Vaksinasi::vaksinTerakhirPerBurung()
            ->filter(fn ($v) => $v->sudah_jatuh_tempo)
            ->count();

        $stok = (float) StokMakanan::query()->sum('jumlah_stok');

        // Saldo Kas Umum + bucket 30 hari (window terbatas, kolom tanggal ber-index)
        $saldoKas = (float) Keuangan::query()->whereNull('financial_tab_id')
            ->selectRaw("COALESCE(SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE -jumlah END), 0) AS s")->value('s');
        $kas30 = Keuangan::query()->whereNull('financial_tab_id')
            ->where('tanggal', '>=', now('Asia/Jakarta')->subDays(29)->toDateString())
            ->selectRaw("tanggal, SUM(CASE WHEN tipe = 'masuk' THEN jumlah ELSE 0 END) AS masuk, SUM(CASE WHEN tipe = 'keluar' THEN jumlah ELSE 0 END) AS keluar")
            ->groupBy('tanggal')->orderBy('tanggal')
            ->get()
            ->map(fn ($r) => ['tanggal' => (string) $r->tanggal, 'masuk' => (float) $r->masuk, 'keluar' => (float) $r->keluar])
            ->all();

        $transaksiTerbaru = Keuangan::query()->orderByDesc('id')->limit(5)
            ->get(['id', 'tipe', 'kategori', 'jumlah', 'tanggal'])
            ->map(fn ($t) => [
                'tipe' => $t->tipe,
                'kategori' => $t->kategori,
                'jumlah' => (float) $t->jumlah,
                'tanggal' => $t->tanggal->translatedFormat('d M Y'),
            ])->all();

        $aktivitasTerbaru = DailyActivity::query()->orderByDesc('activity_date')->limit(5)
            ->get(['id', 'title', 'activity_date'])
            ->map(fn ($a) => ['title' => $a->title, 'tanggal' => optional($a->activity_date)->translatedFormat('d M Y') ?? ''])
            ->all();

        return [
            'burung' => [
                'total' => (int) array_sum($burungFase->all()),
                'dewasa' => (int) ($burungFase['dewasa'] ?? 0),
                'anakan' => (int) ($burungFase['anakan'] ?? 0),
                'spesies' => $burungSpesies->all(),
                'spesiesLainnya' => $burungLainnya,
            ],
            'telur' => [
                'diinkubasi' => (int) ($telurStatus['diinkubasi'] ?? 0),
                'menetas' => (int) ($telurStatus['menetas'] ?? 0),
            ],
            'vaksin' => $vaksin,
            'stok' => $stok,
            'keuangan' => ['saldoKas' => $saldoKas],
            'kas30' => $kas30,
            'transaksiTerbaru' => $transaksiTerbaru,
            'aktivitasTerbaru' => $aktivitasTerbaru,
        ];
    }
}
