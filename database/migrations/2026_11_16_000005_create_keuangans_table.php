<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('keuangans', function (Blueprint $table) {
            $table->id();
            $table->string('tipe', 10);                    // masuk|keluar
            $table->string('kategori', 50);                // Gaji|Operasional|Pembangunan|Penjualan|Lainnya
            $table->decimal('jumlah', 14, 2);
            $table->date('tanggal');
            $table->text('keterangan')->nullable();
            $table->timestamps();

            $table->index(['tipe', 'tanggal']);
            $table->index('kategori');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('keuangans');
    }
};
