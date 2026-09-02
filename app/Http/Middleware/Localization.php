<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;

class Localization
{
    public function handle(Request $request, Closure $next)
    {
        // Prioritas plain cookie 'locale' (stabil, tidak dienkripsi ulang -> aman untuk
        // cache Vary: Cookie); fallback ke session untuk kompatibilitas kunjungan lama.
        $locale = $request->cookie('locale') ?: Session::get('locale');
        if ($locale && in_array($locale, ['en', 'id'])) {
            App::setLocale($locale);
        }

        return $next($request);
    }
}
