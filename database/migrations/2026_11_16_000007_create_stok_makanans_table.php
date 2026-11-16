<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stok_makanans', function (Blueprint $table) {
            $table->id();
            $table->string('nama', 100);
            $table->decimal('jumlah_stok', 10, 2);
            $table->string('satuan', 20)->default('kg');
            $table->decimal('konsumsi_per_hari', 10, 2);
            $table->text('catatan')->nullable();
            $table->timestamps();

            $table->index('nama');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stok_makanans');
    }
};
