<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Management Farm Baru & Lama:
 * - kandangs.lokasi: penanda farm ('baru' default | 'lama') — papan farm lama
 *   dibuat lewat UI (tombol Tambah Kandang), seed farm lama tidak ada.
 * - burung_pindahs: riwayat perpindahan burung (ditempatkan/dikeluarkan) —
 *   pool burung tetap satu, farm hanya papan kandang berbeda.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('kandangs', function (Blueprint $table) {
            $table->string('lokasi', 10)->default('baru')->after('tipe'); // baru|lama
            $table->index('lokasi');
        });

        Schema::create('burung_pindahs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burung_id')->constrained('burungs')->cascadeOnDelete();
            $table->foreignId('kandang_id')->nullable()->constrained('kandangs')->nullOnDelete();
            $table->string('aksi', 20); // ditempatkan|dikeluarkan
            $table->string('keterangan', 200)->nullable();
            $table->timestamps();

            $table->index(['burung_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('burung_pindahs');
        Schema::table('kandangs', function (Blueprint $table) {
            $table->dropIndex(['lokasi']);
            $table->dropColumn('lokasi');
        });
    }
};
