<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Uploader;

/**
 * Melayani unduhan CV (PDF) yang diunggah dari panel admin.
 */
final class CvController extends Controller
{
    public function download(): void
    {
        $file = basename(setting('cv_file', ''));

        if ($file === '') {
            $this->notFound();
        }

        $path = Uploader::documentDirectory() . '/' . $file;

        if (!is_file($path)) {
            $this->notFound();
        }

        $name = trim((string) setting('cv_download_name', 'CV'));
        $name = preg_replace('~[^A-Za-z0-9._ -]+~u', '-', $name) ?: 'CV';
        $name = trim($name, " .-") ?: 'CV';

        header('Content-Type: application/pdf');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . $name . '.pdf"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, max-age=0, must-revalidate');

        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'HEAD') {
            exit;
        }

        readfile($path);
        exit;
    }

    private function notFound(): never
    {
        http_response_code(404);
        header('Content-Type: text/plain; charset=UTF-8');
        echo 'CV belum tersedia.';
        exit;
    }
}
