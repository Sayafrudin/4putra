<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Gabungkan inventaris duplikat per (nama_spesies, fase):
        // survivor = id terkecil, stok = penjumlahan seluruh duplikat,
        // transaksi yang menunjuk duplikat diarahkan ke survivor.
        $groups = DB::table('inventaris_burung')
            ->select('nama_spesies', 'fase', DB::raw('MIN(id) as survivor'), DB::raw('SUM(stok) as total_stok'))
            ->groupBy('nama_spesies', 'fase')
            ->havingRaw('COUNT(*) > 1')
            ->get();

        foreach ($groups as $group) {
            $dupeIds = DB::table('inventaris_burung')
                ->where('nama_spesies', $group->nama_spesies)
                ->where('fase', $group->fase)
                ->where('id', '!=', $group->survivor)
                ->pluck('id');

            DB::table('transaksi_chatbot')
                ->whereIn('inventaris_id', $dupeIds)
                ->update(['inventaris_id' => $group->survivor]);

            DB::table('inventaris_burung')
                ->where('id', $group->survivor)
                ->update(['stok' => $group->total_stok]);

            DB::table('inventaris_burung')->whereIn('id', $dupeIds)->delete();
        }

        // FK longgar: inventaris boleh dihapus, riwayat transaksi tetap ada.
        Schema::table('transaksi_chatbot', function (Blueprint $table) {
            $table->dropForeign(['inventaris_id']);
            $table->foreign('inventaris_id')
                ->references('id')->on('inventaris_burung')
                ->nullOnDelete();
        });

        // Kunci unik agar duplikat (spesies + fase) tidak terulang.
        Schema::table('inventaris_burung', function (Blueprint $table) {
            $table->unique(['nama_spesies', 'fase']);
        });
    }

    public function down(): void
    {
        Schema::table('inventaris_burung', function (Blueprint $table) {
            $table->dropUnique(['nama_spesies', 'fase']);
        });

        Schema::table('transaksi_chatbot', function (Blueprint $table) {
            $table->dropForeign(['inventaris_id']);
            $table->foreign('inventaris_id')
                ->references('id')->on('inventaris_burung')
                ->restrictOnDelete();
        });
    }
};
