<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDomain
{
    /**
     * Admin dashboard hanya berjalan di local (php artisan serve).
     * Host produksi (mis. 4putra.vercel.app) yang menyentuh /admin/* → 404,
     * sehingga tidak mengundang percobaan login dari publik.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Local development: localhost, 127.0.0.1, *.test, *.local → boleh
        if (in_array($host, ['localhost', '127.0.0.1']) || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return $next($request);
        }

        // Host eksternal (produksi): admin tidak tersedia
        abort(404);
    }
}
