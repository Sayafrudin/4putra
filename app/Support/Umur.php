<?php

namespace App\Support;

/** Format umur hari: <30 hari = "N hari"; selanjutnya "N bulan M hari" (30 hari = 1 bulan). */
class Umur
{
    public static function label(?int $hari): ?string
    {
        if ($hari === null) {
            return null;
        }

        if ($hari < 30) {
            return $hari.' hari';
        }

        $bulan = intdiv($hari, 30);
        $sisa = $hari % 30;

        return $sisa === 0
            ? $bulan.' bulan'
            : $bulan.' bulan '.$sisa.' hari';
    }
}
