<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // ============================================================
        // LANGKAH 1: Rebuild NONCLUSTERED → CLUSTERED untuk tabel chatbot
        // (AUTO_RANDOM tidak bisa diterapkan tanpa AUTO_INCREMENT asli,
        //  jadi cukup CLUSTERED saja untuk performa query yang lebih baik)
        // ============================================================

        $nonClusteredTables = [
            'pelanggan', 'percakapan', 'transaksi_chatbot',
            'pembayarans', 'notifikasi_admins', 'inventaris_burung',
        ];

        foreach ($nonClusteredTables as $table) {
            DB::statement("ALTER TABLE {$table} DROP PRIMARY KEY, ADD PRIMARY KEY (id) CLUSTERED");
        }

        // ============================================================
        // LANGKAH 2: INDEX untuk kolom yang sering di-query
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
        // LANGKAH 3: ALTER TABLE CACHE untuk tabel kecil
        // ============================================================
        DB::statement('ALTER TABLE inventaris_burung CACHE');
        DB::statement('ALTER TABLE collections CACHE');

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function down(): void
    {
        DB::statement('SET @@tidb_allow_remove_auto_inc = ON');
        DB::statement('SET FOREIGN_KEY_CHECKS = 0');

        // Hapus index
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

        // Kembalikan ke NONCLUSTERED
        foreach (['pelanggan', 'percakapan', 'transaksi_chatbot', 'pembayarans', 'notifikasi_admins', 'inventaris_burung'] as $table) {
            DB::statement("ALTER TABLE {$table} DROP PRIMARY KEY, ADD PRIMARY KEY (id) NONCLUSTERED");
        }

        DB::statement('SET FOREIGN_KEY_CHECKS = 1');
    }
};
