<?php

use App\Http\Middleware\Localization;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        // Paling luar: retry sekali saat koneksi DB gagal sesaat (SSL abort TiDB)
        $middleware->prepend(\App\Http\Middleware\RetryDbConnection::class);

        // --- DAFTARKAN DISINI ---
        $middleware->web(append: [
            Localization::class,
            \App\Http\Middleware\TrackActivity::class,
        ]);

        $middleware->alias([
            'admin.auth' => \App\Http\Middleware\AdminAuth::class,
            'admin.only' => \App\Http\Middleware\AdminOnly::class,
            'admin.domain' => \App\Http\Middleware\AdminDomain::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
