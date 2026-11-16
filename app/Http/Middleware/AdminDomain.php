<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDomain
{
    /**
     * /admin/* hanya hidup di:
     *  - local development (localhost, 127.0.0.1, *.test, *.local), dan
     *  - host admin produksi khusus (config app.admin_host ← env ADMIN_HOST,
     *    saat ini management4putra-m4p.vercel.app untuk petinggi 4PUTRA).
     * Host lain — termasuk host publik 4putra.vercel.app — membalas 404
     * sehingga jalur admin tidak terungkap ke publik.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        // Local development: localhost, 127.0.0.1, ::1 (IPv6), *.test, *.local → boleh
        if (in_array($host, ['localhost', '127.0.0.1', '[::1]', '::1']) || str_ends_with($host, '.test') || str_ends_with($host, '.local')) {
            return $next($request);
        }

        // Produksi: hanya host admin khusus yang boleh
        $adminHost = config('app.admin_host');
        if ($host !== null && $adminHost !== null && strcasecmp($host, $adminHost) === 0) {
            return $next($request);
        }

        abort(404);
    }
}
