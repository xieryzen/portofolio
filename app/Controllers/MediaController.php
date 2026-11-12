<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Uploader;

/**
 * Melayani file upload dari storage/ agar tidak bergantung
 * pada konfigurasi web server (aman di Pterodactyl).
 */
final class MediaController extends Controller
{
    private const TYPES = [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
    ];

    public function show(string $file): void
    {
        $name = basename(rawurldecode($file));
        $extension = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (!isset(self::TYPES[$extension]) || !preg_match('~^[A-Za-z0-9._-]+$~', $name)) {
            $this->notFound();

            return;
        }

        $path = Uploader::directory() . '/' . $name;
        if (!is_file($path)) {
            $this->notFound();

            return;
        }

        $mtime = (int) filemtime($path);
        $etag = '"' . md5($name . $mtime . (string) filesize($path)) . '"';

        header('Content-Type: ' . self::TYPES[$extension]);
        header('Content-Length: ' . (string) filesize($path));
        header('Cache-Control: public, max-age=31536000, immutable');
        header('ETag: ' . $etag);
        header('X-Content-Type-Options: nosniff');
        header('Content-Disposition: inline; filename="' . $name . '"');

        if ($extension === 'svg') {
            // SVG buatan sistem hanya untuk cover demo; tetap batasi agresif.
            header("Content-Security-Policy: default-src 'none'; style-src 'unsafe-inline'; sandbox");
        }

        $ifNoneMatch = trim((string) ($_SERVER['HTTP_IF_NONE_MATCH'] ?? ''));
        if ($ifNoneMatch === $etag) {
            http_response_code(304);
            exit;
        }

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;
        }

        readfile($path);
        exit;
    }

    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'Not found';
        exit;
    }
}
