<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpFoundation\Response;

class RetryDbConnection
{
    public function handle(Request $request, Closure $next): Response
    {
        try {
            return $next($request);
        } catch (QueryException $e) {
            // Kegagalan koneksi sesaat (SSL abort saat handshake TiDB, koneksi
            // mati karena idle, dsb.) -> buka koneksi baru dan coba sekali lagi.
            if (preg_match('/SSL|aborted|gone away|Connection refused|Connection reset|Lost connection/i', $e->getMessage())) {
                DB::reconnect();

                return $next($request);
            }

            throw $e;
        }
    }
}
