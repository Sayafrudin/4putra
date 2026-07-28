<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Drop foreign keys terlebih dahulu
        $foreignKeys = [
            ['percakapan', 'percakapan_pelanggan_id_foreign'],
            ['transaksi_chatbot', 'transaksi_chatbot_pelanggan_id_foreign'],
            ['transaksi_chatbot', 'transaksi_chatbot_inventaris_id_foreign'],
            ['pembayarans', 'pembayarans_transaksi_id_foreign'],
            ['notifikasi_admins', 'notifikasi_admins_pelanggan_id_foreign'],
        ];

        foreach ($foreignKeys as [$table, $fk]) {
            try {
                DB::statement("ALTER TABLE `{$table}` DROP FOREIGN KEY `{$fk}`");
            } catch (\Exception $e) {
                // FK sudah tidak ada
            }
        }

        // Recreate setiap tabel dengan AUTO_INCREMENT
        // Urutan: tabel tanpa FK dependency duluan
        $tables = [
            'pelanggan',
            'inventaris_burung',
            'percakapan',
            'transaksi_chatbot',
            'pembayarans',
            'notifikasi_admins',
        ];

        foreach ($tables as $table) {
            if (! Schema::hasTable($table)) {
                continue;
            }

            $tempTable = "_old_{$table}";

            // Hapus CACHE jika ada (inventaris_burung punya CACHE ON)
            try {
                DB::statement("ALTER TABLE `{$table}` NOCACHE");
            } catch (\Exception $e) {
            }

            $createSql = DB::select("SHOW CREATE TABLE `{$table}`")[0]->{'Create Table'};

            // Tambahkan AUTO_INCREMENT ke CREATE TABLE statement
            $maxId = DB::table($table)->max('id') ?? 0;
            $nextId = $maxId + 1;

            // Buat CREATE TABLE baru dengan AUTO_INCREMENT
            $newCreateSql = preg_replace(
                '/`id` bigint unsigned NOT NULL/',
                '`id` bigint unsigned NOT NULL AUTO_INCREMENT',
                $createSql
            );
            $newCreateSql = preg_replace(
                '/PRIMARY KEY \(`id`\)/',
                'PRIMARY KEY (`id`)',
                $newCreateSql
            );
            // Hapus komentar TiDB
            $newCreateSql = preg_replace('/\/\*T!\[.*?\]\s*\w+\s*\*\//', '', $newCreateSql);
            $newCreateSql = preg_replace('/\/\*\s*CACHED\s*ON\s*\*\//', '', $newCreateSql);
            // Hapus baris kosong ganda
            $newCreateSql = preg_replace("/\n\s*\n/", "\n", $newCreateSql);
            // Tambah AUTO_INCREMENT di akhir
            $newCreateSql = rtrim($newCreateSql, " \n\r\t")." AUTO_INCREMENT={$nextId}";

            try {
                DB::statement("RENAME TABLE `{$table}` TO `{$tempTable}`");
                DB::statement($newCreateSql);
                DB::statement("INSERT INTO `{$table}` SELECT * FROM `{$tempTable}`");
                DB::statement("DROP TABLE `{$tempTable}`");
                info("Recreated `{$table}` with AUTO_INCREMENT={$nextId}");
            } catch (\Exception $e) {
                // Rollback jika gagal
                try {
                    DB::statement("DROP TABLE IF EXISTS `{$table}`");
                    DB::statement("RENAME TABLE `{$tempTable}` TO `{$table}`");
                } catch (\Exception $ex) {
                }
                \Log::error("Gagal recreate `{$table}`: {$e->getMessage()}");
            }
        }

        // Tambahkan kembali foreign keys
        DB::statement('ALTER TABLE percakapan ADD CONSTRAINT percakapan_pelanggan_id_foreign FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id) ON DELETE CASCADE');
        DB::statement('ALTER TABLE transaksi_chatbot ADD CONSTRAINT transaksi_chatbot_pelanggan_id_foreign FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD CONSTRAINT transaksi_chatbot_inventaris_id_foreign FOREIGN KEY (inventaris_id) REFERENCES inventaris_burung(id)');
        DB::statement('ALTER TABLE pembayarans ADD CONSTRAINT pembayarans_transaksi_id_foreign FOREIGN KEY (transaksi_id) REFERENCES transaksi_chatbot(id)');
        DB::statement('ALTER TABLE notifikasi_admins ADD CONSTRAINT notifikasi_admins_pelanggan_id_foreign FOREIGN KEY (pelanggan_id) REFERENCES pelanggan(id)');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        // Tidak bisa di-reverse
    }
};
