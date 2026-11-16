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

        if ($adminHost !== null && strcasecmp($request->getHost(), $adminHost) === 0
            && ! preg_match('#^/admin(/|$)#', $request->getPathInfo())) {
            return redirect()->to('/admin')
                ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
        }

        return $next($request);
    }
}
