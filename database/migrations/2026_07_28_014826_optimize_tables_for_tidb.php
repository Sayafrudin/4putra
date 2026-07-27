<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Izinkan perubahan AUTO_INCREMENT → AUTO_RANDOM di TiDB
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');

        // ============================================================
        // 1. AUTO_RANDOM untuk tabel utama (mencegah hotspot di TiDB)
        //    Mengubah kolom id dari AUTO_INCREMENT ke AUTO_RANDOM
        // ============================================================

        // Users — sering di-insert saat login/register
        DB::statement('ALTER TABLE users MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Achievements — CRUD admin
        DB::statement('ALTER TABLE achievements MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Achievement Images — setiap upload foto
        DB::statement('ALTER TABLE achievement_images MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Collections — CRUD admin
        DB::statement('ALTER TABLE collections MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Activity Logs — setiap aksi admin
        DB::statement('ALTER TABLE activity_logs MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Pelanggan — setiap pelanggan baru WA
        DB::statement('ALTER TABLE pelanggan MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Percakapan — setiap pesan masuk/keluar (paling sering di-insert)
        DB::statement('ALTER TABLE percakapan MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Transaksi Chatbot
        DB::statement('ALTER TABLE transaksi_chatbot MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Pembayaran
        DB::statement('ALTER TABLE pembayarans MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Notifikasi Admin
        DB::statement('ALTER TABLE notifikasi_admins MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // Inventaris Burung
        DB::statement('ALTER TABLE inventaris_burung MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_RANDOM(5)');

        // ============================================================
        // 2. INDEX untuk kolom yang sering di-query (WHERE, JOIN, ORDER BY)
        // ============================================================

        // Pelanggan — index tambahan
        DB::statement('ALTER TABLE pelanggan ADD INDEX IF NOT EXISTS idx_pelanggan_sesi (sesi_aktif)');
        DB::statement('ALTER TABLE pelanggan ADD INDEX IF NOT EXISTS idx_pelanggan_terakhir (pesan_terakhir)');

        // Percakapan — sudah ada composite (pelanggan_id, created_at), tambah sumber_balasan
        DB::statement('ALTER TABLE percakapan ADD INDEX IF NOT EXISTS idx_percakapan_sumber (sumber_balasan)');

        // Transaksi Chatbot — index untuk filter status + order by created_at
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_status (status)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_pelanggan (pelanggan_id)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_created (created_at)');

        // Pembayaran — index transaksi_id untuk JOIN
        DB::statement('ALTER TABLE pembayarans ADD INDEX IF NOT EXISTS idx_pembayaran_transaksi (transaksi_id)');
        DB::statement('ALTER TABLE pembayarans ADD INDEX IF NOT EXISTS idx_pembayaran_status (status)');

        // Notifikasi Admin — index dibaca + created_at untuk query unread
        DB::statement('ALTER TABLE notifikasi_admins ADD INDEX IF NOT EXISTS idx_notif_dibaca (dibaca)');
        DB::statement('ALTER TABLE notifikasi_admins ADD INDEX IF NOT EXISTS idx_notif_created (created_at)');

        // Inventaris Burung — index aktif + stok untuk filter available
        DB::statement('ALTER TABLE inventaris_burung ADD INDEX IF NOT EXISTS idx_inventaris_aktif_stok (aktif, stok)');

        // Users — index role untuk filter admin
        DB::statement('ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_role (role)');

        // Activity Logs — index user_id + created_at
        DB::statement('ALTER TABLE activity_logs ADD INDEX IF NOT EXISTS idx_logs_user_created (user_id, created_at)');

        // Achievements — index year untuk sorting
        DB::statement('ALTER TABLE achievements ADD INDEX IF NOT EXISTS idx_achievements_year (year)');

        // Collections — index category + sort_order
        DB::statement('ALTER TABLE collections ADD INDEX IF NOT EXISTS idx_collections_cat_sort (category, sort_order)');

        // Sessions — index last_activity untuk cleanup
        DB::statement('ALTER TABLE sessions ADD INDEX IF NOT EXISTS idx_sessions_last_activity (last_activity)');

        // ============================================================
        // 3. ALTER TABLE CACHE untuk tabel kecil (<64MB, data statis)
        //    Disimpan di memori TiDB untuk akses ultra-cepat
        // ============================================================
        DB::statement('ALTER TABLE inventaris_burung CACHE');
        DB::statement('ALTER TABLE collections CACHE');
    }

    public function down(): void
    {
        // Hapus index yang ditambahkan
        $indexes = [
            ['pelanggan', 'idx_pelanggan_sesi'],
            ['pelanggan', 'idx_pelanggan_terakhir'],
            ['percakapan', 'idx_percakapan_sumber'],
            ['transaksi_chatbot', 'idx_transaksi_status'],
            ['transaksi_chatbot', 'idx_transaksi_pelanggan'],
            ['transaksi_chatbot', 'idx_transaksi_created'],
            ['pembayarans', 'idx_pembayaran_transaksi'],
            ['pembayarans', 'idx_pembayaran_status'],
            ['notifikasi_admins', 'idx_notif_dibaca'],
            ['notifikasi_admins', 'idx_notif_created'],
            ['inventaris_burung', 'idx_inventaris_aktif_stok'],
            ['users', 'idx_users_role'],
            ['activity_logs', 'idx_logs_user_created'],
            ['achievements', 'idx_achievements_year'],
            ['collections', 'idx_collections_cat_sort'],
            ['sessions', 'idx_sessions_last_activity'],
        ];

        foreach ($indexes as [$table, $index]) {
            DB::statement("ALTER TABLE {$table} DROP INDEX IF EXISTS {$index}");
        }

        // Kembalikan AUTO_RANDOM ke AUTO_INCREMENT
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');

        $tables = [
            'users', 'achievements', 'achievement_images', 'collections',
            'activity_logs', 'pelanggan', 'percakapan', 'transaksi_chatbot',
            'pembayarans', 'notifikasi_admins', 'inventaris_burung',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }
};
