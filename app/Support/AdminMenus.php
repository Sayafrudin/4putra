<?php

namespace App\Support;

use Illuminate\Support\Str;

/**
 * Pembaca registry config/admin_menus.php. Fail-open: route yang tidak cocok
 * dengan pola menu manapun dianggap tidak terikat menu (diperbolehkan untuk
 * semua yang sudah lolos admin.auth) — salah daftar tidak akan mengunci admin.
 */
class AdminMenus
{
    /** @return array<string, array{label: string, patterns: array<int, string>}> */
    public static function menus(): array
    {
        return config('admin_menus.menus', []);
    }

    /** Kunci menu yang boleh diatur di form user. */
    public static function keys(): array
    {
        return array_keys(self::menus());
    }

    public static function label(string $key): string
    {
        return self::menus()[$key]['label'] ?? $key;
    }

    /**
     * Kunci menu untuk nama route tertentu, atau null bila route tidak terikat menu.
     * Menu pertama yang cocok menang (pola antar-menu dirancang tidak tumpang tindih).
     */
    public static function menuForRoute(?string $routeName): ?string
    {
        if ($routeName === null) {
            return null;
        }

        foreach (self::menus() as $key => $menu) {
            foreach ($menu['patterns'] as $pattern) {
                if (Str::is($pattern, $routeName)) {
                    return $key;
                }
            }
        }

        return null;
    }

    /** Apakah route termasuk daftar bebas-menu (dashboard, profil, chat, users, dst). */
    public static function isAlwaysAllowed(?string $routeName): bool
    {
        if ($routeName === null) {
            return false;
        }

        foreach (config('admin_menus.always_allowed', []) as $pattern) {
            if (Str::is($pattern, $routeName)) {
                return true;
            }
        }

        return false;
    }

    /** Boolehkan user yang sedang login membuka menu kunci ini? NULL = akses penuh. */
    public static function boleh($user, string $key): bool
    {
        if ($user === null || $user->menu_access === null) {
            return true;
        }

        return in_array($key, (array) $user->menu_access, true);
    }
}
