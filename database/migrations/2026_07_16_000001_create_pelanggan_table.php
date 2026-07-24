<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pelanggan', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_wa', 20)->unique();
            $table->string('nama', 100)->nullable();
            $table->enum('sesi_aktif', ['ai', 'menu', 'manual'])->default('menu');
            $table->json('riwayat_konteks')->nullable();
            $table->timestamp('pesan_terakhir')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pelanggan');
    }
};
