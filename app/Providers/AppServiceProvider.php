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
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
            $this->runMigrationsOnce();
        }
    }

    /**
     * Jalankan migrasi otomatis sekali per deploy di production.
     * Menggunakan cache lock untuk menghindari race condition.
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
                Artisan::call('route:cache');
                Artisan::call('view:cache');

                Cache::put($lockKey, true, now()->addHours(24));
                Log::info('Auto-migrate berhasil dijalankan');
            } catch (\Exception $e) {
                Log::error('Auto-migrate gagal: '.$e->getMessage());
            } finally {
                $lock->release();
            }
        }
    }
}
