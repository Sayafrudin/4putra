<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Buat pesan_pengirim nullable (untuk pesan dari admin/bot)
        DB::statement('ALTER TABLE percakapan MODIFY COLUMN pesan_pengirim TEXT NULL');

        // Tambah 'admin', 'human', 'system' ke enum sumber_balasan
        DB::statement("ALTER TABLE percakapan MODIFY COLUMN sumber_balasan ENUM('groq_ai', 'apriori', 'manual', 'menu', 'admin', 'human', 'system') NOT NULL");
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE percakapan MODIFY COLUMN pesan_pengirim TEXT NOT NULL');
        DB::statement("ALTER TABLE percakapan MODIFY COLUMN sumber_balasan ENUM('groq_ai', 'apriori', 'manual', 'menu') NOT NULL");
    }
};
