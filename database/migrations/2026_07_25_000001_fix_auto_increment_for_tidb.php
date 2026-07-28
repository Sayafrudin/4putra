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
        // Fix AUTO_INCREMENT untuk semua tabel di TiDB.
        // TiDB kadang kehilangan atribut AUTO_INCREMENT setelah DDL operations.
        $tables = [
            'migrations',
            'users',
            'activity_logs',
            'achievements',
            'achievement_images',
            'collections',
        ];

        foreach ($tables as $table) {
            if (! \Illuminate\Support\Facades\Schema::hasTable($table)) {
                continue;
            }

            $maxId = DB::table($table)->max('id') ?? 0;
            $nextId = $maxId + 1;

            try {
                // Gunakan MODIFY COLUMN untuk memastikan atribut AUTO_INCREMENT terpasang
                DB::statement("ALTER TABLE `{$table}` MODIFY COLUMN `id` INT UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT = {$nextId}");
                info("Fixed AUTO_INCREMENT for `{$table}` to {$nextId}");
            } catch (\Exception $e) {
                \Log::warning("Could not fix AUTO_INCREMENT for `{$table}`: {$e->getMessage()}");
            }
        }
    }

    public function down(): void
    {
        // Tidak bisa di-reverse
    }
};
