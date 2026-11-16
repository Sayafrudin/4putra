<?php

namespace Database\Seeders;

use App\Models\Kandang;
use Illuminate\Database\Seeder;

/**
 * Struktur kandang aviary 4Putra (data nyata, idempotent):
 * Blok A 15 kandang kecil, B 14 kecil, C 13 kecil, D 20 medium, E 20 medium.
 */
class KandangSeeder extends Seeder
{
    public function run(): void
    {
        $kandang = [
            'A' => ['jumlah' => 15, 'tipe' => 'macaw'],
            'B' => ['jumlah' => 14, 'tipe' => 'macaw'],
            'C' => ['jumlah' => 13, 'tipe' => 'macaw'],
            'D' => ['jumlah' => 20, 'tipe' => 'medium'],
            'E' => ['jumlah' => 20, 'tipe' => 'medium'],
        ];

        foreach ($kandang as $blok => $info) {
            for ($i = 1; $i <= $info['jumlah']; $i++) {
                Kandang::updateOrCreate(
                    ['blok' => $blok, 'nomor' => $i],
                    ['tipe' => $info['tipe']]
                );
            }
        }
    }
}
