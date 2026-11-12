<?php

declare(strict_types=1);

namespace App;

/**
 * Optimasi gambar saat upload: konversi ke WebP + batasi lebar maksimal.
 *
 * Memakai ekstensi GD bila tersedia. Bila GD atau dukungan WebP tidak ada,
 * file asli dibiarkan apa adanya (fitur tetap aman, hanya tidak teroptimasi).
 */
final class Image
{
    /** @var list<string> */
    private const CONVERTIBLE = ['image/jpeg', 'image/png', 'image/webp'];

    public static function supported(): bool
    {
        return function_exists('imagewebp')
            && function_exists('imagecreatefromstring')
            && function_exists('imagecreatetruecolor');
    }

    /**
     * Konversi satu file gambar ke WebP di folder yang sama.
     *
     * @return array{file: string, width: int, height: int, bytes: int, original_bytes: int}|null
     *         null bila tidak dikonversi (GD tidak tersedia, GIF, atau gagal).
     */
    public static function toWebp(string $path, string $mime, ?string $fileName = null): ?array
    {
        if (!self::supported() || !is_file($path)) {
            return null;
        }

        if (!Config::bool('image_webp', true) || !in_array($mime, self::CONVERTIBLE, true)) {
            return null;
        }

        $originalBytes = (int) (filesize($path) ?: 0);
        $contents = @file_get_contents($path);

        if ($contents === false) {
            return null;
        }

        $source = @imagecreatefromstring($contents);
        if (!$source instanceof \GdImage) {
            return null;
        }

        $width = imagesx($source);
        $height = imagesy($source);

        if ($width < 1 || $height < 1) {
            imagedestroy($source);

            return null;
        }

        $maxWidth = max(320, min(6000, Config::int('image_max_width', 1600)));
        $targetWidth = min($width, $maxWidth);
        $targetHeight = (int) max(1, round($height * ($targetWidth / $width)));

        $canvas = imagecreatetruecolor($targetWidth, $targetHeight);
        if (!$canvas instanceof \GdImage) {
            imagedestroy($source);

            return null;
        }

        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        $transparent = imagecolorallocatealpha($canvas, 0, 0, 0, 127);
        imagefilledrectangle($canvas, 0, 0, $targetWidth, $targetHeight, $transparent);
        imagealphablending($canvas, true);

        imagecopyresampled($canvas, $source, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        $quality = max(45, min(95, Config::int('image_webp_quality', 82)));
        $target = $fileName !== null
            ? dirname($path) . '/' . pathinfo($fileName, PATHINFO_FILENAME) . '.webp'
            : preg_replace('~\.[a-z0-9]+$~i', '', $path) . '.webp';

        $target = (string) $target;

        // Jangan menimpa berkas sumber (misal upload WebP dengan nama sama).
        if ($target === $path) {
            $target = preg_replace('~\.webp$~i', '-opt.webp', $path) ?? ($path . '-opt.webp');
        }

        $written = @imagewebp($canvas, $target, $quality);

        imagedestroy($canvas);
        imagedestroy($source);

        if (!$written || !is_file($target)) {
            return null;
        }

        $newBytes = (int) (filesize($target) ?: 0);

        // Jangan buang kualitas asli bila hasil WebP justru lebih besar (gambar kecil).
        if ($newBytes > 0 && $originalBytes > 0 && $newBytes > $originalBytes * 1.05 && $width <= $maxWidth) {
            @unlink($target);

            return null;
        }

        @chmod($target, 0644);

        return [
            'file' => basename($target),
            'width' => $targetWidth,
            'height' => $targetHeight,
            'bytes' => $newBytes,
            'original_bytes' => $originalBytes,
        ];
    }
}
