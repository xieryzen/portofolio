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
