<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Aviary (kandang besar pra-breeding): 2 kandang tipe 'aviary' tanpa batas isi.
     * Burung tetap memakai kolom burungs.kandang_id yang sama, hanya tipe kandangnya
     * yang berbeda sehingga drag & drop dan dropdown lintas menu bekerja otomatis.
     */
    public function up(): void
    {
        DB::table('kandangs')->updateOrInsert(
            ['blok' => 'V', 'nomor' => 1],
            ['tipe' => 'aviary', 'updated_at' => now()]
        );
        DB::table('kandangs')->updateOrInsert(
            ['blok' => 'V', 'nomor' => 2],
            ['tipe' => 'aviary', 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        DB::table('kandangs')->where('blok', 'V')->whereIn('nomor', [1, 2])->delete();
    }
};
