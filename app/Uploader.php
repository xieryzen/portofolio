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
     * Ekstensi file rilis yang diizinkan.
     */
    private const RELEASE_EXTENSIONS = [
        'zip', 'rar', '7z', 'tar', 'gz', 'pdf', 'apk', 'txt', 'md', 'csv',
        'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'png', 'jpg', 'jpeg', 'webp', 'gif',
    ];

    /**
     * MIME yang tidak boleh diunggah (berpotensi dieksekusi).
     */
    private const BLOCKED_MIME = [
        'text/x-php',
        'application/x-php',
        'application/x-httpd-php',
        'application/x-httpd-php-source',
        'text/html',
        'application/xhtml+xml',
        'image/svg+xml',
        'application/javascript',
        'text/javascript',
        'text/x-shellscript',
        'application/x-sh',
        'application/x-dosexec',
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

        // Optimasi: konversi ke WebP + batasi lebar (bila GD tersedia).
        $optimized = Image::toWebp($destination, $mime, $name);
        if ($optimized !== null) {
            if ($optimized['file'] !== $name) {
                @unlink($destination);
                $name = $optimized['file'];
            }
        }

        return ['ok' => true, 'file' => $name];
    }

    /**
     * Upload CV (PDF) dengan validasi isi file.
     *
     * @param array<string, mixed> $file elemen dari $_FILES
     *
     * @return array{ok: bool, file?: string, original_name?: string, size?: int, error?: string}
     */
    public static function document(array $file): array
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

        $maxMb = max(1, Config::int('cv_max_mb', 10));
        if ((int) ($file['size'] ?? 0) > $maxMb * 1024 * 1024) {
            return ['ok' => false, 'error' => 'Ukuran CV maksimal ' . $maxMb . ' MB.'];
        }

        if ((int) ($file['size'] ?? 0) === 0) {
            return ['ok' => false, 'error' => 'File CV kosong (0 byte).'];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || (!is_uploaded_file($tmpPath) && !is_file($tmpPath))) {
            return ['ok' => false, 'error' => 'File upload tidak ditemukan.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($tmpPath));

        if ($mime !== 'application/pdf') {
            return ['ok' => false, 'error' => 'CV harus berupa berkas PDF.'];
        }

        $handle = @fopen($tmpPath, 'rb');
        if ($handle !== false) {
            $head = (string) fread($handle, 5);
            fclose($handle);

            if (strncmp($head, '%PDF-', 5) !== 0) {
                return ['ok' => false, 'error' => 'Berkas bukan PDF yang valid.'];
            }
        }

        $directory = self::documentDirectory();
        $fileName = 'cv-' . date('Ymd') . '-' . bin2hex(random_bytes(6)) . '.pdf';
        $destination = $directory . '/' . $fileName;

        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $destination)
            : rename($tmpPath, $destination);

        if (!$moved) {
            return ['ok' => false, 'error' => 'Gagal menyimpan CV. Periksa izin folder storage/documents.'];
        }

        @chmod($destination, 0644);

        $original = (string) ($file['name'] ?? 'cv.pdf');

        return [
            'ok' => true,
            'file' => $fileName,
            'original_name' => self::safeOriginalName($original, 'pdf'),
            'size' => (int) filesize($destination),
        ];
    }

    public static function deleteDocument(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $path = self::documentDirectory() . '/' . basename($fileName);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function documentDirectory(): string
    {
        $directory = base_path('storage/documents');

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder dokumen tidak bisa dibuat: ' . $directory);
        }

        return $directory;
    }

    public static function maxCvMb(): int
    {
        return max(1, Config::int('cv_max_mb', 10));
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

    /**
     * Upload file rilis/unduhan (ZIP, PDF, APK, dokumen, dll).
     *
     * @param array<string, mixed> $file elemen dari $_FILES
     *
     * @return array{ok: bool, file?: string, original_name?: string, size?: int, extension?: string, error?: string}
     */
    public static function release(array $file): array
    {
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['ok' => false, 'error' => 'File rilis tidak valid.'];
        }

        if ((int) $file['error'] === UPLOAD_ERR_NO_FILE) {
            return ['ok' => false, 'error' => 'Tidak ada file yang dipilih.'];
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage((int) $file['error'])];
        }

        $maxMb = max(1, Config::int('release_max_mb', 50));
        $maxBytes = $maxMb * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            return ['ok' => false, 'error' => 'Ukuran file rilis maksimal ' . $maxMb . ' MB.'];
        }

        if ((int) ($file['size'] ?? 0) === 0) {
            return ['ok' => false, 'error' => 'File rilis kosong (0 byte).'];
        }

        $original = (string) ($file['name'] ?? '');
        $extension = strtolower((string) pathinfo($original, PATHINFO_EXTENSION));

        if (!in_array($extension, self::RELEASE_EXTENSIONS, true)) {
            return ['ok' => false, 'error' => 'Format file tidak diizinkan. Gunakan: ' . strtoupper(implode(', ', self::RELEASE_EXTENSIONS)) . '.'];
        }

        $tmpPath = (string) ($file['tmp_name'] ?? '');
        if ($tmpPath === '' || (!is_uploaded_file($tmpPath) && !is_file($tmpPath))) {
            return ['ok' => false, 'error' => 'File upload tidak ditemukan.'];
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mime = strtolower((string) $finfo->file($tmpPath));

        if (in_array($mime, self::BLOCKED_MIME, true)) {
            return ['ok' => false, 'error' => 'Tipe file tersebut tidak diizinkan demi keamanan.'];
        }

        // Cegah skrip PHP yang disamarkan sebagai file teks/gambar.
        $handle = @fopen($tmpPath, 'rb');
        if ($handle !== false) {
            $head = (string) fread($handle, 256);
            fclose($handle);

            if (stripos($head, '<?php') !== false || stripos($head, '<script') !== false) {
                return ['ok' => false, 'error' => 'File mengandung kode yang tidak diizinkan.'];
            }
        }

        $directory = self::releaseDirectory();
        $fileName = sprintf('rel-%s-%s.%s', date('Ymd'), bin2hex(random_bytes(6)), $extension);
        $destination = $directory . '/' . $fileName;

        $moved = is_uploaded_file($tmpPath)
            ? move_uploaded_file($tmpPath, $destination)
            : rename($tmpPath, $destination);

        if (!$moved) {
            return ['ok' => false, 'error' => 'Gagal menyimpan file rilis. Periksa izin folder storage/releases.'];
        }

        @chmod($destination, 0644);

        $safeName = self::safeOriginalName($original, $extension);

        return [
            'ok' => true,
            'file' => $fileName,
            'original_name' => $safeName,
            'size' => (int) filesize($destination),
            'extension' => $extension,
        ];
    }

    public static function deleteRelease(?string $fileName): void
    {
        if (!$fileName) {
            return;
        }

        $path = self::releaseDirectory() . '/' . basename($fileName);

        if (is_file($path)) {
            @unlink($path);
        }
    }

    public static function releaseDirectory(): string
    {
        $directory = base_path('storage/releases');

        if (!is_dir($directory) && !@mkdir($directory, 0775, true) && !is_dir($directory)) {
            throw new RuntimeException('Folder rilis tidak bisa dibuat: ' . $directory);
        }

        return $directory;
    }

    public static function maxReleaseMb(): int
    {
        return max(1, Config::int('release_max_mb', 50));
    }

    private static function safeOriginalName(string $original, string $extension): string
    {
        $name = basename(str_replace('\\', '/', $original));
        $name = preg_replace('~[^A-Za-z0-9._ ()\-]+~u', '-', $name) ?? $name;
        $name = trim($name, " .-");
        $name = mb_substr($name, 0, 180, 'UTF-8');

        if ($name === '' || !str_contains($name, '.')) {
            $name = 'release-' . date('Ymd') . '.' . $extension;
        }

        return $name;
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
