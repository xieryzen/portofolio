<?php

declare(strict_types=1);

namespace App;

use PDO;

/**
 * Pengaturan situs (tabel settings) dengan cache per-request.
 */
final class Settings
{
    /** @var array<string, string>|null */
    private static ?array $cache = null;

    /**
     * @return array<string, string>
     */
    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }

        self::$cache = [];

        try {
            $rows = Database::connection()->query('SELECT setting_key, setting_value FROM settings')->fetchAll();
            foreach ($rows as $row) {
                self::$cache[(string) $row['setting_key']] = (string) ($row['setting_value'] ?? '');
            }
        } catch (\Throwable) {
            self::$cache = [];
        }

        return self::$cache;
    }

    public static function get(string $key, string $default = ''): string
    {
        $all = self::all();

        return array_key_exists($key, $all) && $all[$key] !== '' ? $all[$key] : $default;
    }

    public static function set(string $key, string $value): void
    {
        $pdo = Database::connection();
        $driver = $pdo->getAttribute(PDO::ATTR_DRIVER_NAME);

        if ($driver === 'mysql') {
            $statement = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
                 ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
            );
        } else {
            $statement = $pdo->prepare(
                'INSERT INTO settings (setting_key, setting_value) VALUES (:key, :value)
                 ON CONFLICT(setting_key) DO UPDATE SET setting_value = excluded.setting_value'
            );
        }

        $statement->execute(['key' => $key, 'value' => $value]);
        self::$cache[$key] = $value;
    }

    /**
     * @param array<string, string> $values
     */
    public static function setMany(array $values): void
    {
        foreach ($values as $key => $value) {
            self::set($key, $value);
        }
    }

    public static function flush(): void
    {
        self::$cache = null;
    }
}
