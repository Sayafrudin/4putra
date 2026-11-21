<?php

namespace App\Support;

use App\Models\Burung;

/** Format identitas burung (sumber tunggal): "Nama (Spesies - Gender, Ring N)" —
 *  bagian opsional hilang bila kosong, sesuai konvensi identitas di AGENTS.md. */
class Identitas
{
    /** Satu sisi pasangan/induk. */
    public static function sisi(?Burung $b): string
    {
        if (! $b) {
            return '?';
        }

        return $b->nama.' ('.$b->spesies.($b->gender_label ? ' - '.$b->gender_label : '').($b->nomor_ring ? ', Ring '.$b->nomor_ring : '').')';
    }

    /** Pasangan: "X (..) × Y (..)". Bila salah satu sisi tak tersedia (relasi
     *  hilang/data lama), pakai label tersimpan agar info historis tak hilang. */
    public static function pasangan(?Burung $a, ?Burung $b, ?string $fallback = null): string
    {
        if ($a && $b) {
            return static::sisi($a).' × '.static::sisi($b);
        }

        if ($fallback !== null) {
            return $fallback;
        }

        return static::sisi($a ?: $b);
    }
}
