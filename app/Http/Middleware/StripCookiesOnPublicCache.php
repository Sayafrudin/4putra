<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Buang Set-Cookie pada response halaman publik yang sudah ditandai cacheable
 * (Cache-Control mengandung s-maxage, diset oleh CachePublic di level route).
 *
 * Harus berjalan di posisi global paling luar: cookie session/XSRF ditambahkan
 * oleh StartSession & AddQueuedCookiesToResponse (level grup web) yang berada
 * DI DALAM middleware route — strip di level route akan di-override lagi.
 *
 * Tanpa ini: cookie ter-encrypt ulang tiap response (IV acak) -> nilai Set-Cookie
 * selalu beda -> cache-key browser/edge (Vary: Cookie) tak pernah stabil dan
 * Vercel menolak cache response ber-Set-Cookie.
 */
class StripCookiesOnPublicCache
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_contains($response->headers->get('Cache-Control', ''), 's-maxage')) {
            foreach ($response->headers->getCookies() as $cookie) {
                $response->headers->removeCookie($cookie->getName(), $cookie->getPath(), $cookie->getDomain());
            }
        }

        return $response;
    }
}
