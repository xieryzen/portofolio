<?php

declare(strict_types=1);

namespace App;

use RuntimeException;

/**
 * Penanganan upload gambar yang aman:
 * - validasi MIME lewat finfo + getimagesize
 * - ukuran maksimal dari konfigurasi
 * - nama file acak, tidak memakai nama asli user
 * - disembunyikan di storage/ dan dilayani lewat route /media
 */
final class Uploader
{
    private const ALLOWED = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    /**
     * @param array<string, mixed> $file elemen dari $_FILES
     *
     * @return array{ok: bool, file?: string, error?: string}
     */
    public static function image(array $file, string $prefix = 'img'): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'error' => 'File tidak valid.'];
        }

        if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Tidak ada file yang dipilih.'];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage((int) $file['error'])];
        }

        $maxBytes = Config::int('max_upload_mb', 6) * 1024 * 1024;
        if (($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'Ukuran gambar maksimal ' . Config::int('max_upload_mb', 6) . ' MB.'];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || !is_uploaded_file($tmpPath) && !is_file($tmpPath)) {
            return ['ok' => false, 'error' => 'File upload tidak ditemukan.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = (string) $finfo->file($tmpPath);

        if (!isset(self::ALLOWED[$mime])) {
            return ['ok' => false, 'error' => 'Format gambar harus JPG, PNG, WEBP, atau GIF.'];
        }

        $info = @getimagesize($tmpPath);
        if ($info === false || ($info[0] ?? 0) < 1 || ($info[1] ?? 0) < 1) {
            return ['ok' => false, 'error' => 'File yang diunggah bukan gambar yang valid.'];
        }

        $directory = self::directory();
        $extension = self::ALLOWED[$mime];
        $name = sprintf(
            '%s-%s-%s.%s',
            preg_replace('~[^a-z0-9]~i', '', $prefix) ?: 'img',
            date('Ymd'),
            bin2hex(random_bytes(6)),
            $extension
        );

        $destination = $directory . '/' . $name;

        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $destination)
            : rename($tmpPath, $destination);

        if (!$moved) {
            return ['ok' => false, 'error' => 'Gagal menyimpan gambar. Periksa izin folder storage/uploads.'];
        }

        @chmod($destination, 0644);

        return ['ok' => true, 'file' => $name];
    }

    public static function delete(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $name = basename($fileName);
        $path = self::directory() . '/' . $name;

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function directory(): string
    {
        $directory = base_path('storage/uploads');

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder upload tidak bisa dibuat: ' . $directory);
        }

        return $directory;
    }

    public static function maxUploadMb(): int
    {
        return Config::int('max_upload_mb', 6);
    }

    private static function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'Ukuran gambar melebihi batas server.',
            UPLOAD_ERR_PARTIAL => 'Upload gambar tidak selesai, silakan coba lagi.',
            UPLOAD_ERR_NO_TMP_DIR => 'Folder sementara server tidak tersedia.',
            UPLOAD_ERR_CANT_WRITE => 'Server gagal menulis file. Periksa izin folder.',
            UPLOAD_ERR_EXTENSION => 'Upload dihentikan oleh ekstensi PHP.',
            default => 'Gagal mengunggah gambar.',
        };
    }
}
