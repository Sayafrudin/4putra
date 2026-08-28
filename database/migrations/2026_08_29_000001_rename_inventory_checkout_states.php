<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Superset: tambah state baru + pertahankan state lama sementara
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory', 'checkout', 'inventory_select', 'checkout_qty', 'human') DEFAULT 'menu'");

        // Migrasi data lama → baru
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'inventory_select' WHERE sesi_aktif = 'inventory'");
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'checkout_qty' WHERE sesi_aktif = 'checkout'");

        // Final: hapus state lama dari ENUM
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory_select', 'checkout_qty', 'human') DEFAULT 'menu'");
    }

    public function down(): void
    {
        // Kembalikan ke state lama
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory', 'checkout', 'inventory_select', 'checkout_qty', 'human') DEFAULT 'menu'");
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'inventory' WHERE sesi_aktif = 'inventory_select'");
        DB::statement("UPDATE pelanggan SET sesi_aktif = 'checkout' WHERE sesi_aktif = 'checkout_qty'");
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('menu', 'ai', 'inventory', 'checkout', 'human') DEFAULT 'menu'");
    }
};
