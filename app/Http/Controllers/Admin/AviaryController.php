<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Burung;
use App\Models\Kandang;

/**
 * Aviary (kandang besar pra-breeding): penampungan burung sebelum masuk
 * kandang breeding. Fitur papan sama dengan Kandang, tapi isinya tanpa batas.
 * Aksi letakkan/kosongkan/CRUD burung memakai endpoint KandangController yang sama.
 */
class AviaryController extends Controller
{
    public function index()
    {
        $aviaries = Kandang::with('burungs')
            ->where('tipe', 'aviary')
            ->orderBy('nomor')
            ->get();

        // Pool = hanya burung bebas yang BUKAN baby (fase anakan) — baby punya domain sendiri di menu Baby.
        // Burung kandang breeding tidak ditawarkan — aturan: breeding → aviary ditolak server-side.
        $pool = Burung::with('kandang:id,blok,nomor,tipe')
            ->whereNull('kandang_id')
            ->where('fase', '!=', 'anakan')
            ->orderByDesc('created_at')
            ->get();

        $total = Burung::count();
        $terpasang = $aviaries->sum(fn ($a) => $a->burungs->count());

        return view('admin.aviary.index', compact('aviaries', 'pool', 'total', 'terpasang') + ['dataEkspor' => $this->barisEkspor($aviaries, $pool)]);
    }

    /** Baris export Excel aviary: satu baris per burung + aviary kosong + pool. */
    private function barisEkspor($aviaries, $pool)
    {
        $baris = [];
        foreach ($aviaries as $k) {
            if ($k->burungs->isEmpty()) {
                $baris[] = [$k->blok, $k->label, '(kosong)', '', '', '', '', '', ''];
                continue;
            }
            foreach ($k->burungs as $b) {
                $baris[] = [$k->blok, $k->label, $b->nama, $b->spesies, $b->fase_label, $b->gender_label ?: '-', $b->nomor_ring ?: '-', $b->nomor_dna ?: '-', $b->catatan ?: '-'];
            }
        }
        foreach ($pool as $b) {
            $baris[] = ['-', 'Belum ditempatkan', $b->nama, $b->spesies, $b->fase_label, $b->gender_label ?: '-', $b->nomor_ring ?: '-', $b->nomor_dna ?: '-', $b->catatan ?: '-'];
        }

        return $baris;
    }
}
