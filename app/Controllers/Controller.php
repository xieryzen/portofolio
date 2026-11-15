<?php

declare(strict_types=1);

namespace App\Controllers;

use App\View;

abstract class Controller
{
    /**
     * @param array<string, mixed> $data
     */
    protected function view(string $view, array $data = [], ?string $layout = 'layouts/app'): void
    {
        View::render($view, $data, $layout);
    }

    protected function redirect(string $path, int $status = 302): never
    {
        \redirect($path, $status);
    }

    /**
     * Simpan error validasi + input lama lalu kembali ke halaman sebelumnya.
     *
     * @param array<string, string> $errors
     * @param array<string, mixed> $old
     */
    protected function backWithErrors(array $errors, array $old = [], string $fallback = '/'): never
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old'] = array_map(
            static fn ($value) => is_array($value) ? $value : (is_scalar($value) ? (string) $value : ''),
            $old
        );

        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        $target = $fallback;

        if ($referer !== '') {
            $host = parse_url($referer, PHP_URL_HOST);
            $currentHost = $_SERVER['HTTP_HOST'] ?? '';
            if ($host && ($host === $currentHost || parse_url($referer, PHP_URL_HOST) === $currentHost)) {
                $target = $referer;
            }
        }

        \flash('error', (string) reset($errors));
        \redirect($target);
    }

    /**
     * True bila ukuran kiriman melebihi post_max_size sehingga $_FILES kosong.
     * Tanpa cek ini, pengguna hanya melihat "pilih berkas" padahal berkasnya kebesaran.
     */
    protected function uploadTooLarge(): bool
    {
        $length = (int) ($_SERVER['CONTENT_LENGTH'] ?? 0);
        if ($length <= 0) {
            return false;
        }

        $limit = self::iniBytes((string) ini_get('post_max_size'));

        return $limit > 0 && $length > $limit;
    }

    /**
     * Batas unggah efektif dalam MB (nilai terkecil antara upload_max_filesize
     * dan post_max_size) untuk ditampilkan pada pesan galat.
     */
    protected function uploadLimitMb(): int
    {
        $upload = self::iniBytes((string) ini_get('upload_max_filesize'));
        $post = self::iniBytes((string) ini_get('post_max_size'));
        $candidates = array_filter([$upload, $post], static fn (int $v): bool => $v > 0);
        if ($candidates === []) {
            return 0;
        }

        return max(1, (int) floor(min($candidates) / 1048576));
    }

    private static function iniBytes(string $value): int
    {
        $value = trim($value);
        if ($value === '' || $value === '-1') {
            return 0;
        }

        $unit = strtolower(substr($value, -1));
        $number = (float) $value;

        return (int) match ($unit) {
            'g' => $number * 1024 * 1024 * 1024,
            'm' => $number * 1024 * 1024,
            'k' => $number * 1024,
            default => $number,
        };
    }

    /**
     * Ambil input POST yang sudah dipangkas.
     */
    protected function input(string $key, mixed $default = ''): mixed
    {
        $value = $_POST[$key] ?? $default;

        if (is_string($value)) {
            return trim($value);
        }

        return $value;
    }

    protected function inputString(string $key, string $default = ''): string
    {
        $value = $this->input($key, $default);

        return is_scalar($value) ? trim((string) $value) : $default;
    }

    /**
     * Bersihkan input teks panjang dari karakter kontrol.
     */
    protected function cleanText(string $value, int $maxLength = 20000): string
    {
        $value = str_replace(["\r\n", "\r"], "\n", $value);
        $value = preg_replace('~[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]~', '', $value) ?? '';

        return mb_substr(trim($value), 0, $maxLength, 'UTF-8');
    }

    protected function clearFormState(): void
    {
        unset($_SESSION['_old'], $_SESSION['_errors']);
    }
}
