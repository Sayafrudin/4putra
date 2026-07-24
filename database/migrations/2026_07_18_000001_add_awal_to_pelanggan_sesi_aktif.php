<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('ai', 'menu', 'manual', 'human', 'awal') DEFAULT 'awal'");
    }

    public function down(): void
    {
        DB::statement("ALTER TABLE pelanggan MODIFY COLUMN sesi_aktif ENUM('ai', 'menu', 'manual', 'human') DEFAULT 'menu'");
    }
};
