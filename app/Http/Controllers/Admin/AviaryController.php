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

        return view('admin.aviary.index', compact('aviaries', 'pool', 'total', 'terpasang'));
    }
}
