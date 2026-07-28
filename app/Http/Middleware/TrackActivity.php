<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class TrackActivity
{
    public function handle(Request $request, Closure $next): Response
    {
        if (Auth::check()) {
            $userId = Auth::id();
            $cacheKey = "last_active_{$userId}";

            // Hanya update DB setiap 5 menit, bukan setiap request
            if (! Cache::has($cacheKey)) {
                Auth::user()->update(['last_active_at' => now()]);
                Cache::put($cacheKey, true, 300); // 5 menit
            }
        }

        return $next($request);
    }
}
