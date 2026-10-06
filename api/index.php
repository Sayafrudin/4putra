<?php

// Warm-up cold start per instance serverless: filesystem runtime read-only
// kecuali /tmp. Config + view cache ditulis ke /tmp (APP_CONFIG_CACHE &
// VIEW_COMPILED_PATH sudah diarahkan vercel.json) sekali per instance —
// request berikutnya pada instance yang sama booting tanpa parsing ulang.
// Route cache sengaja tidak dipakai: routes/web.php masih memakai closure.
$marker = sys_get_temp_dir().'/.laravel-warmed';
if (! file_exists($marker)) {
    $artisan = dirname(__DIR__).DIRECTORY_SEPARATOR.'artisan';
    foreach (['config:cache', 'view:cache'] as $cmd) {
        @exec(escapeshellarg(PHP_BINARY).' '.escapeshellarg($artisan).' '.escapeshellarg($cmd), $out, $code);
    }
    @touch($marker);
}

require __DIR__.'/../public/index.php';
