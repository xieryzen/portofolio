<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Release;
use App\Uploader;

/**
 * Melayani unduhan file rilis. File disimpan di storage/releases
 * sehingga tidak bisa diakses langsung lewat web server.
 */
final class DownloadController extends Controller
{
    public function file(string $id): void
    {
        $release = Release::find((int) $id);
        $isAdmin = Auth::isAdmin();

        if (!$release) {
            $this->notFound();
        }

        $publishedProject = ($release['project_status'] ?? '') === 'published';
        $publishedRelease = (int) ($release['is_published'] ?? 0) === 1;

        if ((!$publishedProject || !$publishedRelease) && !$isAdmin) {
            $this->notFound();
        }

        $fileName = (string) ($release['file_name'] ?? '');
        if ($fileName === '') {
            $this->notFound();
        }

        $path = Uploader::releaseDirectory() . '/' . basename($fileName);
        if (!is_file($path)) {
            $this->notFound();
        }

        Release::incrementDownloads((int) $release['id']);

        $original = (string) ($release['original_name'] ?? ('release-' . $release['version'] . '.bin'));
        $original = preg_replace('~[\\r\\n"]~', '', basename($original)) ?: 'release.bin';
        $asciiName = preg_replace('~[^A-Za-z0-9._ -]~', '-', $original) ?: 'release.bin';

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . $asciiName . '"; filename*=UTF-8\'\'' . rawurlencode($original));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store, max-age=0');
        header('X-Download-Options: noopen');

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
        echo 'File rilis tidak ditemukan.';
        exit;
    }
}
