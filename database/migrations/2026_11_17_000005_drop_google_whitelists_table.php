<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    // Whitelist Gmail digantikan tabel users sebagai sumber tunggal akses
    // Google (tambah akun via Kelola Akun User, password opsional).
    public function up(): void
    {
        Schema::dropIfExists('google_whitelists');
    }

    public function down(): void
    {
        // Tidak dikembalikan; whitelist lama tidak dipakai lagi.
    }
};
