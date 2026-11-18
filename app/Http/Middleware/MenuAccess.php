<?php

namespace App\Http\Middleware;

use App\Support\AdminMenus;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Hak akses menu per akun admin (users.menu_access JSON; NULL = akses penuh).
 * Fail-open: hanya route yang EKSPLISIT ter-mapping ke menu di
 * config/admin_menus.php yang bisa diblokir — salah daftar tidak mengunci
 * halaman; drift daftar ditangkap oleh test peta route (AdminMenuAccessTest).
 */
class MenuAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null || $user->isAdmin() === false) {
            return $next($request);
        }

        $routeName = $request->route()?->getName();

        // Kelola Akun User = eksklusif admin penuh (menu_access NULL), mencegah
        // admin terbatas mengubah hak akses akun lain.
        if ($user->menu_access !== null
            && (str_starts_with((string) $routeName, 'admin.users.')
                || str_starts_with((string) $routeName, 'admin.activity.'))) {
            return $this->tolak($request);
        }

        // Admin penuh → semua menu.
        if ($user->menu_access === null) {
            return $next($request);
        }

        // Fail-open: route tidak terikat menu manapun → boleh.
        $menu = AdminMenus::menuForRoute($routeName);
        if ($menu === null || AdminMenus::isAlwaysAllowed($routeName)) {
            return $next($request);
        }

        if (! in_array($menu, (array) $user->menu_access, true)) {
            return $this->tolak($request);
        }

        return $next($request);
    }

    private function tolak(Request $request): Response
    {
        if ($request->expectsJson()) {
            return response()->json(['ok' => false, 'message' => 'Anda tidak memiliki akses ke menu tersebut.'], 403);
        }

        return redirect()->route('admin.dashboard')->with('error', 'Anda tidak memiliki akses ke menu tersebut.');
    }
}
