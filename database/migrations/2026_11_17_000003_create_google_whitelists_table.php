<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Whitelist Gmail yang boleh login via Google (petinggi 4PUTRA) — dikelola admin
        // di halaman Kelola Akun User → tab Akses Google. Gmail di luar daftar ditolak.
        Schema::create('google_whitelists', function (Blueprint $table) {
            $table->id();
            $table->string('email')->unique();
            $table->string('nama', 100)->nullable();
            $table->string('role', 10)->default('user'); // admin|user
            $table->timestamps();
        });

        DB::table('google_whitelists')->updateOrInsert(
            ['email' => 'syafrudintdm@gmail.com'],
            ['nama' => 'Syafrudin', 'role' => 'admin', 'created_at' => now(), 'updated_at' => now()]
        );
    }

    public function down(): void
    {
        Schema::dropIfExists('google_whitelists');
    }
};
