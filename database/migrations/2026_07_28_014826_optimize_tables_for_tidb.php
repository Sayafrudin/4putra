<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');

        // ============================================================
        // Tabel CLUSTERED sudah AUTO_RANDOM dari migration sebelumnya.
        // Untuk tabel NONCLUSTERED: rebuild primary key sebagai CLUSTERED
        // lalu langsung convert ke AUTO_RANDOM.
        // ============================================================

        $nonClusteredTables = [
            'pelanggan', 'percakapan', 'transaksi_chatbot',
            'pembayarans', 'notifikasi_admins', 'inventaris_burung',
        ];

        foreach ($nonClusteredTables as $table) {
            // Rebuild primary key sebagai CLUSTERED + AUTO_RANDOM sekaligus
            DB::statement("ALTER TABLE {$table} DROP PRIMARY KEY, ADD PRIMARY KEY (id) CLUSTERED AUTO_RANDOM(5)");
        }

        // ============================================================
        // INDEX untuk kolom yang sering di-query
        // ============================================================

        DB::statement('ALTER TABLE pelanggan ADD INDEX IF NOT EXISTS idx_pelanggan_sesi (sesi_aktif)');
        DB::statement('ALTER TABLE pelanggan ADD INDEX IF NOT EXISTS idx_pelanggan_terakhir (pesan_terakhir)');
        DB::statement('ALTER TABLE percakapan ADD INDEX IF NOT EXISTS idx_percakapan_sumber (sumber_balasan)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_status (status)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_pelanggan (pelanggan_id)');
        DB::statement('ALTER TABLE transaksi_chatbot ADD INDEX IF NOT EXISTS idx_transaksi_created (created_at)');
        DB::statement('ALTER TABLE pembayarans ADD INDEX IF NOT EXISTS idx_pembayaran_transaksi (transaksi_id)');
        DB::statement('ALTER TABLE pembayarans ADD INDEX IF NOT EXISTS idx_pembayaran_status (status)');
        DB::statement('ALTER TABLE notifikasi_admins ADD INDEX IF NOT EXISTS idx_notif_dibaca (dibaca)');
        DB::statement('ALTER TABLE notifikasi_admins ADD INDEX IF NOT EXISTS idx_notif_created (created_at)');
        DB::statement('ALTER TABLE inventaris_burung ADD INDEX IF NOT EXISTS idx_inventaris_aktif_stok (aktif, stok)');
        DB::statement('ALTER TABLE users ADD INDEX IF NOT EXISTS idx_users_role (role)');
        DB::statement('ALTER TABLE activity_logs ADD INDEX IF NOT EXISTS idx_logs_user_created (user_id, created_at)');
        DB::statement('ALTER TABLE achievements ADD INDEX IF NOT EXISTS idx_achievements_year (year)');
        DB::statement('ALTER TABLE collections ADD INDEX IF NOT EXISTS idx_collections_cat_sort (category, sort_order)');
        DB::statement('ALTER TABLE sessions ADD INDEX IF NOT EXISTS idx_sessions_last_activity (last_activity)');

        // ============================================================
        // ALTER TABLE CACHE untuk tabel kecil
        // ============================================================
        DB::statement('ALTER TABLE inventaris_burung CACHE');
        DB::statement('ALTER TABLE collections CACHE');
    }

    public function down(): void
    {
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');

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

        // Kembalikan ke NONCLUSTERED + AUTO_INCREMENT
        $tables = [
            'users', 'achievements', 'achievement_images', 'collections',
            'activity_logs', 'pelanggan', 'percakapan', 'transaksi_chatbot',
            'pembayarans', 'notifikasi_admins', 'inventaris_burung',
        ];

        foreach ($tables as $table) {
            DB::statement("ALTER TABLE {$table} DROP PRIMARY KEY, ADD PRIMARY KEY (id) NONCLUSTERED");
            DB::statement("ALTER TABLE {$table} MODIFY COLUMN id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT");
        }
    }
};
