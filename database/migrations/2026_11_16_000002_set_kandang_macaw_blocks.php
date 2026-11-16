<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Blok A/B/C adalah kandang macaw (bukan "kecil"); D/E tetap medium.
     * Perbaikan data menyusul konfirmasi pemilik farm.
     */
    public function up(): void
    {
        DB::table('kandangs')->whereIn('blok', ['A', 'B', 'C'])->update(['tipe' => 'macaw']);
    }

    public function down(): void
    {
        DB::table('kandangs')->whereIn('blok', ['A', 'B', 'C'])->update(['tipe' => 'kecil']);
    }
};
