<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Superset dulu agar UPDATE data lama tidak ditolak strict mode
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory', 'checkout', 'human', 'manual', 'awal') DEFAULT 'menu'");

        DB::statement("UPDATE pelanggan SET sesi_aktif = 'human' WHERE sesi_aktif = 'manual'");
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'menu' WHERE sesi_aktif = 'awal'");

        // Final 5 state
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory', 'checkout', 'human') DEFAULT 'menu'");
    }

    public function down(): void
    {
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'ai' WHERE sesi_aktif = 'inventory'");
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'menu' WHERE sesi_aktif = 'checkout'");

        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('ai', 'menu', 'manual', 'human', 'awal') DEFAULT 'menu'");
    }
};
