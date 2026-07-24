<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifikasi_admins', function (Blueprint $table) {
            $table->id();
            $table->enum('tipe', ['pesan_masuk', 'pembayaran', 'permintaan_manual', 'lainnya']);
            $table->string('judul', 200);
            $table->text('isi')->nullable();
            $table->foreignId('pelanggan_id')->nullable()->constrained('pelanggan');
            $table->boolean('dibaca')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notifikasi_admins');
    }
};
