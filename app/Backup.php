<?php

declare(strict_types=1);

namespace App;

use PDO;
use Throwable;
use ZipArchive;

/**
 * Cadangan (backup) dan pemulihan (restore) data aplikasi.
 *
 * - Database SQLite + folder upload, rilis, dan dokumen (CV) dibungkus jadi satu berkas ZIP.
 * - Restore memvalidasi isi ZIP sebelum menyentuh berkas apa pun.
 */
final class Backup
{
    /** Folder yang ikut dicadangkan. */
    private const FOLDERS = ['uploads', 'releases', 'documents'];

    /** Batas ukuran tiap berkas di dalam ZIP saat restore. */
    private const MAX_ENTRY_BYTES = 25 * 1024 * 1024;

    public static function driver(): string
    {
        return Config::string('db.connection', 'sqlite');
    }

    public static function isSqlite(): bool
    {
        return self::driver() === 'sqlite';
    }

    public static function databasePath(): string
    {
        $path = Config::string('db.database', 'storage/database.sqlite');

        return str_starts_with($path, '/') ? $path : base_path($path);
    }

    public static function databaseSize(): int
    {
        $path = self::databasePath();

        return is_file($path) ? (int) filesize($path) : 0;
    }

    /**
     * @return array{files: int, bytes: int}
     */
    public static function storageStats(): array
    {
        $files = 0;
        $bytes = 0;

        foreach (self::FOLDERS as $folder) {
            $directory = base_path('storage/' . $folder);

            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if ($file->isFile()) {
                    $files++;
                    $bytes += (int) $file->getSize();
                }
            }
        }

        return ['files' => $files, 'bytes' => $bytes];
    }

    public static function maxMb(): int
    {
        return max(5, min(1024, Config::int('backup_max_mb', 200)));
    }

    public static function backupsDir(): string
    {
        $directory = base_path('storage/backups');

        if (!is_dir($directory)) {
            @mkdir($directory, 0775, true);
        }

        return $directory;
    }

    /**
     * @return list<array{name: string, size: int, time: int}>
     */
    public static function listBackups(): array
    {
        $directory = self::backupsDir();
        $items = [];

        foreach (glob($directory . '/*.sqlite') ?: [] as $file) {
            $items[] = [
                'name' => basename($file),
                'size' => (int) filesize($file),
                'time' => (int) filemtime($file),
            ];
        }

        usort($items, static fn (array $a, array $b): int => $b['time'] <=> $a['time']);

        return $items;
    }

    /**
     * Bungkus database + berkas unggahan menjadi satu ZIP sementara.
     *
     * @return array{ok: bool, path?: string, error?: string}
     */
    public static function createArchive(): array
    {
        if (!class_exists(ZipArchive::class)) {
            return ['ok' => false, 'error' => 'Ekstensi PHP zip tidak tersedia di server ini.'];
        }

        $path = tempnam(sys_get_temp_dir(), 'portfolio-backup-');
        if ($path === false) {
            return ['ok' => false, 'error' => 'Tidak bisa membuat berkas sementara.'];
        }

        $path .= '.zip';

        $zip = new ZipArchive();
        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            return ['ok' => false, 'error' => 'Tidak bisa membuat berkas ZIP.'];
        }

        $info = [
            'Portfolio CMS — cadangan',
            'Dibuat: ' . date('Y-m-d H:i:s'),
            'Driver database: ' . self::driver(),
            'Versi aplikasi: ' . (defined('APP_VERSION') ? APP_VERSION : '1.x'),
            '',
            self::isSqlite()
                ? 'Isi: database.sqlite, uploads/, releases/, documents/'
                : 'Isi: uploads/, releases/, documents/ (database MySQL tidak disertakan; gunakan mysqldump)',
        ];
        $zip->addFromString('INFO.txt', implode("\n", $info) . "\n");

        if (self::isSqlite() && is_file(self::databasePath())) {
            $zip->addFile(self::databasePath(), 'database.sqlite');
        }

        $added = 0;
        foreach (self::FOLDERS as $folder) {
            $directory = base_path('storage/' . $folder);
            if (!is_dir($directory)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS)
            );

            foreach ($iterator as $file) {
                if (!$file->isFile() || $file->getFilename() === '.gitkeep') {
                    continue;
                }

                $zip->addFile($file->getPathname(), $folder . '/' . $file->getFilename());
                $added++;
            }
        }

        if (!$zip->close()) {
            @unlink($path);

            return ['ok' => false, 'error' => 'Gagal menulis berkas ZIP.'];
        }

        return ['ok' => true, 'path' => $path, 'files' => $added];
    }

    /**
     * Pulihkan database dan berkas dari ZIP yang diunggah.
     *
     * @return array{ok: bool, message?: string, error?: string}
     */
    public static function restore(string $zipPath): array
    {
        if (!class_exists(ZipArchive::class)) {
            return ['ok' => false, 'error' => 'Ekstensi PHP zip tidak tersedia di server ini.'];
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return ['ok' => false, 'error' => 'Berkas bukan ZIP yang valid.'];
        }

        $entries = [];
        $hasDatabase = false;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);
            if ($stat === false) {
                continue;
            }

            $name = str_replace('\\', '/', (string) $stat['name']);

            if (str_ends_with($name, '/')) {
                continue;
            }

            if ($name === 'database.sqlite') {
                $hasDatabase = true;
                $entries[] = $index;
                continue;
            }

            if ($name === 'INFO.txt') {
                continue;
            }

            $allowed = false;
            foreach (self::FOLDERS as $folder) {
                if (preg_match('~^' . $folder . '/[A-Za-z0-9][A-Za-z0-9._-]*$~', $name) === 1) {
                    $allowed = true;
                    break;
                }
            }

            if (!$allowed) {
                $zip->close();

                return ['ok' => false, 'error' => 'ZIP berisi berkas yang tidak dikenal: ' . $name];
            }

            if (preg_match('~\.(php|phtml|phar|html?|js|htaccess)$~i', $name) === 1) {
                $zip->close();

                return ['ok' => false, 'error' => 'ZIP berisi tipe berkas yang diblokir: ' . $name];
            }

            // SVG hanya boleh bila tidak mengandung skrip.
            if (str_ends_with(strtolower($name), '.svg')) {
                $svg = (string) $zip->getFromIndex($index);
                if (stripos($svg, '<script') !== false || preg_match('~\son[a-z]+\s*=~i', $svg) === 1) {
                    $zip->close();

                    return ['ok' => false, 'error' => 'SVG di dalam ZIP mengandung skrip dan diblokir: ' . $name];
                }
            }

            if ((int) $stat['size'] > self::MAX_ENTRY_BYTES) {
                $zip->close();

                return ['ok' => false, 'error' => 'Berkas di dalam ZIP terlalu besar: ' . $name];
            }

            $entries[] = $index;
        }

        if ($entries === []) {
            $zip->close();

            return ['ok' => false, 'error' => 'ZIP tidak berisi data yang bisa dipulihkan.'];
        }

        if ($hasDatabase && !self::isSqlite()) {
            $zip->close();

            return ['ok' => false, 'error' => 'Restore database hanya didukung untuk SQLite. Untuk MySQL gunakan mysqldump.'];
        }

        $databaseRestored = false;

        if ($hasDatabase) {
            $contents = $zip->getFromName('database.sqlite');

            if ($contents === false || !str_starts_with($contents, "SQLite format 3")) {
                $zip->close();

                return ['ok' => false, 'error' => 'Berkas database.sqlite di dalam ZIP tidak valid.'];
            }

            $temporary = tempnam(sys_get_temp_dir(), 'portfolio-db-');
            if ($temporary === false || file_put_contents($temporary, $contents) === false) {
                $zip->close();

                return ['ok' => false, 'error' => 'Gagal menyiapkan database sementara.'];
            }

            if (!self::looksLikePortfolioDatabase($temporary)) {
                @unlink($temporary);
                $zip->close();

                return ['ok' => false, 'error' => 'Database di dalam ZIP bukan database Portfolio CMS.'];
            }

            $backup = self::snapshotCurrentDatabase();
            if ($backup === null) {
                @unlink($temporary);
                $zip->close();

                return ['ok' => false, 'error' => 'Gagal membuat cadangan database saat ini.'];
            }

            $target = self::databasePath();
            if (!is_dir(dirname($target))) {
                @mkdir(dirname($target), 0775, true);
            }

            if (!copy($temporary, $target)) {
                @unlink($temporary);
                $zip->close();

                return ['ok' => false, 'error' => 'Gagal menimpa database. Periksa izin folder storage.'];
            }

            @unlink($temporary);
            @chmod($target, 0644);
            $databaseRestored = true;
        }

        $restoredFiles = 0;

        foreach ($entries as $index) {
            $stat = $zip->statIndex($index);
            if ($stat === false) {
                continue;
            }

            $name = str_replace('\\', '/', (string) $stat['name']);
            if ($name === 'database.sqlite') {
                continue;
            }

            [$folder, $fileName] = explode('/', $name, 2);
            $destinationDir = base_path('storage/' . $folder);
            if (!is_dir($destinationDir)) {
                @mkdir($destinationDir, 0775, true);
            }

            $contents = $zip->getFromIndex($index);
            if ($contents === false) {
                continue;
            }

            if (file_put_contents($destinationDir . '/' . $fileName, $contents) !== false) {
                @chmod($destinationDir . '/' . $fileName, 0644);
                $restoredFiles++;
            }
        }

        $zip->close();

        $parts = [];
        if ($databaseRestored) {
            $parts[] = 'database dipulihkan';
        }
        $parts[] = $restoredFiles . ' berkas unggahan dipulihkan';

        return [
            'ok' => true,
            'message' => ucfirst(implode(', ', $parts)) . '. Cadangan database sebelum restore disimpan di storage/backups.',
        ];
    }

    private static function looksLikePortfolioDatabase(string $path): bool
    {
        try {
            $pdo = new PDO('sqlite:' . $path);
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type = 'table'")
                ->fetchAll(PDO::FETCH_COLUMN);

            return in_array('users', $tables, true) && in_array('projects', $tables, true);
        } catch (Throwable) {
            return false;
        }
    }

    private static function snapshotCurrentDatabase(): ?string
    {
        $source = self::databasePath();

        if (!is_file($source)) {
            return null;
        }

        $directory = self::backupsDir();
        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            return null;
        }

        $target = $directory . '/sebelum-restore-' . date('Ymd-His') . '.sqlite';

        if (!copy($source, $target)) {
            return null;
        }

        @chmod($target, 0644);

        // Simpan maksimal 5 cadangan otomatis.
        $items = self::listBackups();
        foreach (array_slice($items, 5) as $item) {
            @unlink($directory . '/' . $item['name']);
        }

        return $target;
    }
}
