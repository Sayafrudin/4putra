<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminHostRedirect
{
    /**
     * Host admin khusus (config app.admin_host ← env ADMIN_HOST) hanya melayani
     * /admin/*: semua rute lain di-host itu diarahkan ke /admin, sehingga petinggi
     * cukup membuka subdomain admin dan langsung tiba di dashboard admin.
     * Host lain (localhost & host publik) tidak tersentuh middleware ini.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $adminHost = config('app.admin_host');

        // Pengecualian: jalur infrastruktur login & aset di host admin tidak dialihkan,
        // karena auth middleware mengarahkan guest /admin -> /login (rute publik bersama).
        // Tanpa ini terjadi loop /admin <-> /login (ERR_TOO_MANY_REDIRECTS).
        if ($adminHost !== null && strcasecmp($request->getHost(), $adminHost) === 0
            && ! preg_match('#^/admin(/|$)#', $request->getPathInfo())
            && ! preg_match('#^/(login(/|$)|auth/|storage/|up(/|$))#', $request->getPathInfo())) {
            return redirect()->to('/admin')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $next($request);
    }
}
