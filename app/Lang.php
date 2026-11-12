<?php

declare(strict_types=1);

namespace App;

/**
 * Dukungan dua bahasa untuk halaman publik (ID/EN).
 *
 * Pilihan bahasa diambil berurutan dari: parameter `?lang=`, cookie `lang`,
 * lalu default `id`. Area admin tetap berbahasa Indonesia.
 */
final class Lang
{
    public const SUPPORTED = ['id', 'en'];

    private const COOKIE = 'lang';

    private static ?string $locale = null;

    /** @var array<string, string>|null */
    private static ?array $strings = null;

    public static function boot(): void
    {
        $requested = strtolower(trim((string) ($_GET['lang'] ?? '')));

        if (in_array($requested, self::SUPPORTED, true)) {
            self::$locale = $requested;

            if (!headers_sent()) {
                setcookie(self::COOKIE, $requested, [
                    'expires' => time() + 60 * 60 * 24 * 365,
                    'path' => app_base_url() === '' ? '/' : rtrim(app_base_url(), '/') . '/',
                    'samesite' => 'Lax',
                    'secure' => is_secure(),
                    'httponly' => false,
                ]);
            }

            return;
        }

        if (self::$locale !== null) {
            return;
        }

        $cookie = strtolower(trim((string) ($_COOKIE[self::COOKIE] ?? '')));
        $accept = strtolower((string) ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));

        if (in_array($cookie, self::SUPPORTED, true)) {
            self::$locale = $cookie;

            return;
        }

        // Hanya tebak dari header browser bila pengunjung belum pernah memilih.
        self::$locale = str_starts_with($accept, 'en') ? 'en' : 'id';
    }

    public static function locale(): string
    {
        if (self::$locale === null) {
            self::boot();
        }

        return self::$locale ?? 'id';
    }

    public static function is(string $locale): bool
    {
        return self::locale() === $locale;
    }

    public static function get(string $key, ?string $fallback = null): string
    {
        if (self::$strings === null) {
            $file = base_path('lang/' . self::locale() . '.php');
            self::$strings = is_file($file) ? (array) require $file : [];
        }

        if (isset(self::$strings[$key])) {
            return (string) self::$strings[$key];
        }

        // Cadangan: kamus Indonesia agar tidak pernah tampil key mentah.
        $fallbackFile = base_path('lang/id.php');
        if (is_file($fallbackFile)) {
            $default = (array) require $fallbackFile;
            if (isset($default[$key])) {
                return (string) $default[$key];
            }
        }

        return $fallback ?? $key;
    }

    /**
     * URL halaman saat ini dengan parameter bahasa lain (untuk tombol ID/EN).
     */
    public static function switchUrl(string $locale): string
    {
        $path = current_path();
        $query = $_GET;
        $query['lang'] = $locale;

        $qs = http_build_query($query);

        return app_url($path) . ($qs !== '' ? '?' . $qs : '');
    }

    public static function htmlLang(): string
    {
        return self::locale() === 'en' ? 'en' : 'id';
    }
}
