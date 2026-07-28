<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminDomain
{
    /**
     * Domain admin yang diizinkan mengakses route /admin/*.
     * Jika request dari domain lain, redirect ke domain admin.
     */
    protected $adminDomains = [
        'admin4putra.vercel.app',
        // Tambahkan domain admin custom lain di sini
    ];

    /**
     * Domain public (website utama).
     */
    protected $publicDomains = [
        '4putra.vercel.app',
        // Tambahkan domain public custom lain di sini
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $path = $request->path();

        // Di local development, lewati pengecekan domain
        if ($this->isLocal($host)) {
            return $next($request);
        }

        $isAdminDomain = in_array($host, $this->adminDomains);
        $isPublicDomain = in_array($host, $this->publicDomains);

        // Jika mengakses /admin/* dari domain public → redirect ke domain admin
        if ($isPublicDomain && str_starts_with($path, 'admin')) {
            $adminUrl = 'https://'.$this->adminDomains[0].'/'.$path;
            if ($request->getQueryString()) {
                $adminUrl .= '?'.$request->getQueryString();
            }

            return redirect($adminUrl, 308);
        }

        // Jika mengakses route public dari domain admin → redirect ke domain public
        if ($isAdminDomain && ! str_starts_with($path, 'admin')) {
            $publicUrl = 'https://'.$this->publicDomains[0].'/'.$path;
            if ($request->getQueryString()) {
                $publicUrl .= '?'.$request->getQueryString();
            }

            return redirect($publicUrl, 308);
        }

        return $next($request);
    }

    protected function isLocal(string $host): bool
    {
        return in_array($host, ['localhost', '127.0.0.1']) || str_ends_with($host, '.test') || str_ends_with($host, '.local');
    }
}
