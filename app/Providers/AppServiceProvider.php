<?php

namespace App\Providers;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Hot file Vite basi (dev server mati tapi public/hot tertinggal) membuat
        // browser meminta CSS/JS ke server yang sudah mati — seluruh desain hang
        // dan tampak "berantakan". Hapus otomatis agar Laravel jatuh ke aset build.
        // Cukup sekali per proses: fsockopen yang ditolak balas seketika (±0-1ms).
        static $hotChecked = false;
        if (! $hotChecked) {
            $hotChecked = true;
            $this->hapusHotFileBasi();
        }

        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            $this->runMigrationsOnce();
        }
    }

    /**
     * Hapus public/hot bila dev server Vite-nya tidak hidup.
     * Saat npm run dev dinyalakan lagi, Vite membuat ulang hot file —
     * jadi dua-duanya pulih sendiri tanpa langkah manual.
     */
    private function hapusHotFileBasi(): void
    {
        $hot = public_path('hot');
        if (! is_file($hot)) {
            return;
        }

        $raw = trim((string) file_get_contents($hot));
        $host = parse_url($raw, PHP_URL_HOST);
        $port = parse_url($raw, PHP_URL_PORT) ?: 5173;
        if (! $host) {
            return;
        }

        $sock = @fsockopen($host, (int) $port, $errno, $errstr, 1.0);
        if (is_resource($sock)) {
            fclose($sock); // dev server hidup → biarkan (HMR aktif)

            return;
        }

        @unlink($hot);
    }

    /**
     * Jalankan migrasi otomatis sekali per deploy di production.
     * Menggunakan cache lock untuk menghindari race condition.
     * Catatan: cache file Vercel bersifat per-instance (/tmp), jadi ini
     * berjalan sekali per instance dingin — bukan sekali per aplikasi.
     */
    private function runMigrationsOnce(): void
    {
        $lockKey = 'migration_ran_'.($this->app->version() ?? 'v1');

        if (Cache::has($lockKey)) {
            return;
        }

        $lock = Cache::lock('migration_lock', 30);

        if ($lock->get()) {
            try {
                Artisan::call('migrate', ['--force' => true]);
                Artisan::call('config:cache');
                Artisan::call('view:cache');
                // route:cache sengaja tidak dipanggil: routes/web.php memakai
                // closure sehingga perintah ini selalu gagal.

                Cache::put($lockKey, true, now()->addHours(24));
                Log::info('Auto-migrate berhasil dijalankan');
            } catch (\Exception $e) {
                Log::error('Auto-migrate gagal: '.$e->getMessage());
                // Tandai tetap supaya request berikutnya tidak mengulang
                // migrate/config:cache di setiap request; dicoba lagi nanti.
                Cache::put($lockKey, true, now()->addMinutes(10));
            } finally {
                $lock->release();
            }
        }
    }
}
