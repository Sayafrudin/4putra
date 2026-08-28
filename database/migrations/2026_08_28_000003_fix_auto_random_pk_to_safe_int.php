<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

return new class extends Migration
{
    /**
     * Perbaikan permanen ID overflow (akar masalah: 5 tabel inti memakai
     * BIGINT UNSIGNED AUTO_RANDOM(5) — TiDB menghasilkan ID acak 64-bit yang
     * ~50% peluangnya melebihi PHP_INT_MAX sehingga route binding & insertGetId
     * rusak). Solusi: re-key baris overflow ke rentang aman (data-preserving),
     * perbaiki FK turunan, lalu rebuild tabel dengan `id INT UNSIGNED
     * AUTO_INCREMENT` (ter-caps 4.29 miliar, aman untuk PHP).
     *
     * TiDB tidak mengizinkan MODIFY tipe kolom PK CLUSTERED (error 8200),
     * sehingga rebuild dilakukan via CREATE tmp + INSERT SELECT + RENAME.
     */
    private const SAFE_LIMIT = 4200000000; // di bawah batas INT UNSIGNED (4294967295)

    private const TABLES = ['collections', 'achievements', 'achievement_images', 'activity_logs', 'users'];

    public function up(): void
    {
        // 1. Re-key baris dengan ID > SAFE_LIMIT, catat pemetaan old→new
        $maps = [];
        foreach (self::TABLES as $t) {
            $safeMax = (int) (DB::table($t)->where('id', '<=', self::SAFE_LIMIT)->max('id') ?? 0);
            $next = $safeMax + 1;
            $maps[$t] = [];

            $overflows = DB::table($t)->where('id', '>', self::SAFE_LIMIT)->orderBy('id')->get(['id']);
            foreach ($overflows as $row) {
                $old = (string) $row->id;
                $maps[$t][$old] = $next;
                DB::table($t)->where('id', $old)->update(['id' => $next]);
                $next++;
            }

            if (! empty($maps[$t])) {
                Log::info("Re-key {$t}: " . count($maps[$t]) . " baris di-re-key mulai ID {$safeMax}.");
            }
        }

        // 2. Perbaiki FK turunan dari pemetaan
        foreach ($maps['achievements'] as $old => $new) {
            DB::table('achievement_images')->where('achievement_id', $old)->update(['achievement_id' => $new]);
        }
        foreach ($maps['users'] as $old => $new) {
            DB::table('activity_logs')->where('user_id', $old)->update(['user_id' => $new]);
        }

        // 3. Rebuild tabel: AUTO_RANDOM → INT UNSIGNED AUTO_INCREMENT
        foreach (self::TABLES as $t) {
            $this->rebuildTable($t);
        }
    }

    private function rebuildTable(string $t): void
    {
        $ddl = DB::selectOne("SHOW CREATE TABLE `{$t}`")->{'Create Table'};
        $lines = explode("\n", $ddl);
        $out = [];
        foreach ($lines as $line) {
            // Ganti definisi kolom id: AUTO_RANDOM → AUTO_INCREMENT int unsigned
            if (preg_match('/^\s*`id`\s/', $line)) {
                $out[] = "  `id` int unsigned NOT NULL AUTO_INCREMENT,";
                continue;
            }
            $line = str_replace([' /*T![clustered_index] CLUSTERED */', ' /*T![auto_rand] AUTO_RANDOM(5) */'], '', $line);
            $out[] = rtrim($line, "\r");
        }
        $newDdl = implode("\n", $out);

        // Bersihkan option TiDB yang tak lagi relevan untuk tabel baru
        $newDdl = preg_replace('/\s*\/\*T!\[auto_rand_base\][^*]*\*\//', '', $newDdl);
        $newDdl = preg_replace('/\s*\/\* CACHED ON \*\//', '', $newDdl);

        $tmp = "{$t}_safe_rebuild";
        DB::statement("DROP TABLE IF EXISTS `{$tmp}`");

        // Ganti nama tabel sumber → nama tabel rebuild
        $newDdl = preg_replace('/^CREATE TABLE `[^`]+`/', "CREATE TABLE `{$tmp}`", $newDdl, 1);
        DB::statement($newDdl);

        // Salin seluruh baris (kolom eksplisit sesuai urutan asli)
        $columns = DB::select(
            'SELECT COLUMN_NAME FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? ORDER BY ORDINAL_POSITION',
            [$t]
        );
        $colList = implode(', ', array_map(fn ($c) => "`{$c->COLUMN_NAME}`", $columns));
        DB::statement("INSERT INTO `{$tmp}` ({$colList}) SELECT {$colList} FROM `{$t}`");

        // Cached table (TiDB) tidak mendukung RENAME → matikan dulu cache-nya
        try {
            DB::statement("ALTER TABLE `{$t}` NOCACHE");
        } catch (\Throwable $e) {
            // Bukan cached table — abaikan
        }

        DB::statement("RENAME TABLE `{$t}` TO `{$t}_old_ar`, `{$tmp}` TO `{$t}`");
        DB::statement("DROP TABLE `{$t}_old_ar`");

        // Pastikan counter berada di atas ID terbesar hasil salin
        $maxId = (int) (DB::table($t)->max('id') ?? 0);
        DB::statement("ALTER TABLE `{$t}` AUTO_INCREMENT = " . ($maxId + 1));
    }

    public function down(): void
    {
        // Tidak dapat dibalik: pemetaan ID lama sudah hilang (by design, one-shot repair)
        Log::warning('Migrasi fix_auto_random_pk_to_safe_int tidak memiliki down path.');
    }
};
