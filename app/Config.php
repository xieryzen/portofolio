<?php

declare(strict_types=1);

namespace App;

/**
 * Pembaca konfigurasi sederhana.
 *
 * Nilai diambil dari variabel environment (Pterodactyl, Docker, shell)
 * lalu dari file .env yang ada di root aplikasi.
 */
final class Config
{
    /** @var array<string, mixed> */
    private static array $items = [];

    private static bool $loaded = false;

    public static function load(string $basePath): void
    {
        if (self::$loaded) {
            return;
        }

        self::loadEnvFile($basePath . '/.env');

        $port = self::firstEnv(['APP_PORT', 'SERVER_PORT', 'PORT'], '8080');
        $host = self::firstEnv(['APP_HOST', 'SERVER_IP', 'HOST'], '0.0.0.0');

        self::$items = [
            'app.name'        => self::env('APP_NAME', 'Portfolio'),
            'app.env'         => self::env('APP_ENV', 'production'),
            'app.debug'       => self::envBool('APP_DEBUG', false),
            'app.key'         => self::env('APP_KEY', ''),
            'app.host'        => $host,
            'app.port'        => (int) $port,
            'app.url'         => rtrim((string) self::env('APP_URL', ''), '/'),
            'app.trust_proxy' => self::envBool('TRUST_PROXY', true),

            'db.connection'   => strtolower((string) self::env('DB_CONNECTION', 'sqlite')),
            'db.database'     => self::env('DB_DATABASE', 'storage/database.sqlite'),
            'db.host'         => self::env('DB_HOST', '127.0.0.1'),
            'db.port'         => (int) self::env('DB_PORT', '3306'),
            'db.name'         => self::env('DB_NAME', 'portfolio'),
            'db.username'     => self::env('DB_USERNAME', ''),
            'db.password'     => self::env('DB_PASSWORD', ''),

            'owner.name'      => self::env('OWNER_NAME', 'Owner'),
            'owner.email'     => self::env('OWNER_EMAIL', 'owner@example.com'),
            'owner.password'  => self::env('OWNER_PASSWORD', 'Owner12345!'),

            'registration'    => self::envBool('ALLOW_REGISTRATION', true),
            'max_upload_mb'   => max(1, (int) self::env('MAX_UPLOAD_MB', '6')),
            'accent'          => self::env('ACCENT_COLOR', '#6d8bff'),
        ];

        self::$loaded = true;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::$items[$key] ?? $default;
    }

    public static function string(string $key, string $default = ''): string
    {
        $value = self::get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        $value = self::get($key, $default);

        if (is_bool($value)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        $value = self::get($key, $default);

        return is_numeric($value) ? (int) $value : $default;
    }

    public static function env(string $key, ?string $default = null): ?string
    {
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? getenv($key);

        if ($value === false || $value === null || $value === '') {
            return $default;
        }

        // Nilai dari getenv bisa berbentuk string "true"/"false".
        if (is_string($value) && in_array(strtolower($value), ['null', '(null)'], true)) {
            return $default;
        }

        return is_string($value) ? $value : (string) $value;
    }

    private static function firstEnv(array $keys, string $default): string
    {
        foreach ($keys as $key) {
            $value = self::env($key);
            if ($value !== null && $value !== '') {
                return $value;
            }
        }

        return $default;
    }

    private static function envBool(string $key, bool $default): bool
    {
        $value = self::env($key);

        if ($value === null) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    /**
     * Parser .env minimal (tanpa dependensi).
     */
    private static function loadEnvFile(string $path): void
    {
        if (!is_file($path) || !is_readable($path)) {
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];

        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '' || str_starts_with($line, '#')) {
                continue;
            }

            if (str_starts_with($line, 'export ')) {
                $line = trim(substr($line, 7));
            }

            $parts = explode('=', $line, 2);
            if (count($parts) !== 2) {
                continue;
            }

            $key = trim($parts[0]);
            $value = trim($parts[1]);

            if ($key === '') {
                continue;
            }

            if ($value !== '' && ($value[0] === '"' || $value[0] === "'")) {
                $quote = $value[0];
                $value = trim($value, $quote);
                if ($quote === '"') {
                    $value = str_replace(['\n', '\r', '\"'], ["\n", "\r", '"'], $value);
                }
            } else {
                // Buang komentar di belakang nilai tanpa tanda kutip.
                $hash = strpos($value, ' #');
                if ($hash !== false) {
                    $value = rtrim(substr($value, 0, $hash));
                }
            }

            // Jangan menimpa variabel yang sudah disediakan environment
            // (Pterodactyl/Docker harus menang).
            if (isset($_ENV[$key]) || getenv($key) !== false) {
                continue;
            }

            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
            putenv($key . '=' . $value);
        }
    }
}
