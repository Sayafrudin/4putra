<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Segarkan label baby lama ke format identitas baru (konvensi AGENTS.md):
 * - induk_label: silsilah dua induk dengan nomor ring bila terisi.
 * - asal_label: "Blok X · Kandang Y" (dulu label kandang saja).
 * Bila relasi telur/induk tak lengkap, Identitas::pasangan memakai label
 * tersimpan — tidak ada informasi yang hilang.
 */
return new class extends Migration
{
    public function up(): void
    {
        $babies = DB::table('burungs')->whereNotNull('telur_id')->get(['id']);

        foreach ($babies as $row) {
            $baby = \App\Models\Burung::with('telur.burung.kandang', 'telur.pasangan')->find($row->id);
            $telur = $baby?->telur;
            if (! $telur) {
                continue;
            }

            $induk = \App\Support\Identitas::pasangan($telur->burung, $telur->pasangan, $baby->induk_label);
            $kandang = $telur->burung?->kandang;
            $asal = $kandang ? 'Blok '.$kandang->blok.' · Kandang '.$kandang->label : $baby->asal_label;

            if ($induk !== $baby->induk_label || $asal !== $baby->asal_label) {
                $baby->update(['induk_label' => $induk, 'asal_label' => $asal]);
            }
        }
    }

    public function down(): void
    {
        // Pembaruan label sekali jalan — tidak reversible.
    }
};
