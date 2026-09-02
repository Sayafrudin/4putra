<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CachePublic
{
    /**
     * Set Cache-Control edge-friendly untuk halaman publik statis.
     * Hanya GET sukses (200); redirect & error lolos tanpa header agar tidak ter-cache di Vercel Edge.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if ($request->isMethod('GET') && $response->isSuccessful()) {
            // Header s-maxage juga menjadi penanda bagi StripCookiesOnPublicCache
            // (global, paling luar) untuk membuang Set-Cookie — lihat middleware tsb.
            $response->headers->set(
                'Cache-Control',
                'public, max-age=60, s-maxage=300, stale-while-revalidate=86400'
            );
            // Edge cache wajib membedakan konten per bahasa (locale di cookie session)
            $response->headers->set('Vary', 'Cookie, Accept-Language');
        }

        return $response;
    }
}
