<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Menu Baby (loloh) + penjualan.
 * Baby = burung fase 'anakan' tanpa kandang, diperkaya jejak silsilah dari telur:
 * telur_id (1 baby per butir), tanggal_lahir (= tanggal menetas), induk_label &
 * asal_label = snapshot dari telur saat konversi (tetap ada walau telur dihapus).
 * penjualans = snapshot penjualan; uang otomatis masuk Kas Umum via keuangan_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('burungs', function (Blueprint $table) {
            $table->foreignId('telur_id')->nullable()->after('kandang_id')->constrained('telurs')->nullOnDelete();
            $table->date('tanggal_lahir')->nullable()->after('telur_id');
            $table->string('induk_label', 200)->nullable()->after('tanggal_lahir');
            $table->string('asal_label', 100)->nullable()->after('induk_label');
            $table->unique('telur_id');
        });

        Schema::create('penjualans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('burung_id')->nullable()->constrained('burungs')->nullOnDelete();
            $table->string('nama_burung', 100);
            $table->string('nomor_ring', 50)->nullable();
            $table->string('induk_label', 200)->nullable();
            $table->unsignedSmallInteger('usia_hari')->nullable();
            $table->decimal('harga', 14, 2);
            $table->date('tanggal_terjual');
            $table->foreignId('keuangan_id')->nullable()->constrained('keuangans')->nullOnDelete();
            $table->text('keterangan')->nullable();
            $table->timestamps();
            $table->index('tanggal_terjual');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('penjualans');
        Schema::table('burungs', function (Blueprint $table) {
            $table->dropUnique(['telur_id']);
            $table->dropConstrainedForeignId('telur_id');
            $table->dropColumn(['tanggal_lahir', 'induk_label', 'asal_label']);
        });
    }
};
