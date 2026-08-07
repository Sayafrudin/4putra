<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Fix auto_increment yang overflow akibat TiDB allocation.
     * TiDB bisa mengalokasikan ID yang sangat besar (> PHP_INT_MAX)
     * sehingga menyebabkan overflow di PHP.
     */
    public function up(): void
    {
        $tables = [
            'users',
            'activity_logs',
            'achievements',
            'achievement_images',
            'collections',
            'pelanggan',
            'percakapan',
            'transaksi_chatbot',
            'pembayarans',
            'notifikasi_admins',
            'inventaris_burung',
        ];

        $maxSafeId = 1000000000; // 1 miliar - batas aman untuk PHP integer

        foreach ($tables as $table) {
            if (!Schema::hasTable($table)) {
                continue;
            }

            try {
                // Cek apakah ada ID yang melebihi batas aman
                $maxId = DB::table($table)->max('id');

                if ($maxId === null) {
                    // Tabel kosong, reset auto_increment ke 1
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
                    info("Reset AUTO_INCREMENT for empty table `{$table}` to 1");
                    continue;
                }

                // Konversi ke integer untuk perbandingan
                $maxIdInt = is_numeric($maxId) ? (int) $maxId : 0;

                if ($maxIdInt > $maxSafeId || $maxIdInt < 0) {
                    // ID melebihi batas aman atau overflow, perlu di-fix
                    // Hapus record dengan ID terlalu besar
                    DB::table($table)->where('id', '>', $maxSafeId)->delete();

                    // Cari max ID setelah cleanup
                    $newMaxId = DB::table($table)->max('id') ?? 0;
                    $nextId = (int) $newMaxId + 1;

                    // Reset auto_increment
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId}");
                    info("Fixed AUTO_INCREMENT for `{$table}`: removed overflow IDs, set to {$nextId}");
                } else {
                    // ID masih dalam batas aman, pastikan auto_increment benar
                    $nextId = $maxIdInt + 1;
                    DB::statement("ALTER TABLE `{$table}` AUTO_INCREMENT = {$nextId}");
                    info("Verified AUTO_INCREMENT for `{$table}` at {$nextId}");
                }
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
