<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Fix AUTO_INCREMENT untuk semua tabel di TiDB.
     * TiDB kadang kehilangan property AUTO_INCREMENT setelah DDL operations.
     */
    public function up(): void
    {
        $tables = ['users', 'activity_logs', 'achievements', 'achievement_images', 'collections'];

        foreach ($tables as $table) {
            // Cek apakah tabel ada
            if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            // Ambil max id saat ini dan set AUTO_INCREMENT ke max_id + 1
            $maxId = DB::table($table)->max('id') ?? 0;
            $nextId = $maxId + 1;

            try {
                DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId}");
                $this->command->info("Fixed AUTO_INCREMENT for `{$table}` to {$nextId}");
            } catch (\Exception $e) {
                $this->command->warn("Could not fix AUTO_INCREMENT for `{$table}`: {$e->getMessage()}");
            }
        }
    }

    public function down(): void
    {
        // Tidak bisa di-reverse
    }
};
