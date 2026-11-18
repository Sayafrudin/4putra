<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Tab proyek keuangan. Kas Umum = virtual (transaksi financial_tab_id NULL), tanpa baris di sini.
        Schema::create('financial_tabs', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->decimal('target_anggaran', 14, 2)->default(0); // target anggaran utama (goal)
            $table->timestamps();
        });

        // Rincian biaya tambahan tab proyek (item dinamis, TOTAL BUDGET = target + jumlah nominal).
        Schema::create('financial_tab_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('financial_tab_id')->constrained('financial_tabs')->cascadeOnDelete();
            $table->string('nama', 150);
            $table->decimal('nominal', 14, 2);
            $table->timestamps();

            $table->index('financial_tab_id');
        });

        Schema::table('keuangans', function (Blueprint $table) {
            $table->foreignId('financial_tab_id')->nullable()->after('kategori')->constrained('financial_tabs')->nullOnDelete();
            $table->index('financial_tab_id');
        });
    }

    public function down(): void
    {
        Schema::table('keuangans', function (Blueprint $table) {
            $table->dropForeign(['financial_tab_id']);
            $table->dropColumn('financial_tab_id');
        });
        Schema::dropIfExists('financial_tab_costs');
        Schema::dropIfExists('financial_tabs');
    }
};
