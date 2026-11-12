<?php

declare(strict_types=1);

namespace App\Controllers;

/**
 * Berkas pendukung PWA: manifest dinamis dan halaman offline.
 */
final class PwaController extends Controller
{
    public function manifest(): void
    {
        $name = (string) setting('site_title', 'Portfolio');
        $short = mb_substr($name, 0, 14, 'UTF-8');
        $description = (string) setting('meta_description', '');
        $accent = (string) setting('accent_color', '#6d8bff');

        $manifest = [
            'name' => $name,
            'short_name' => $short,
            'description' => $description,
            'lang' => 'id',
            'dir' => 'ltr',
            'start_url' => app_url('/'),
            'scope' => app_url('/'),
            'display' => 'standalone',
            'display_override' => ['standalone', 'minimal-ui'],
            'orientation' => 'portrait-primary',
            'background_color' => '#060a14',
            'theme_color' => $accent,
            'categories' => ['portfolio', 'business', 'productivity'],
            'icons' => [
                [
                    'src' => app_url('/icons/icon-192.png'),
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => app_url('/icons/icon-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any',
                ],
                [
                    'src' => app_url('/icons/icon-maskable-512.png'),
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable',
                ],
            ],
            'shortcuts' => [
                [
                    'name' => 'Karya',
                    'short_name' => 'Karya',
                    'url' => app_url('/#karya'),
                    'icons' => [['src' => app_url('/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
                [
                    'name' => 'Kontak',
                    'short_name' => 'Kontak',
                    'url' => app_url('/#kontak'),
                    'icons' => [['src' => app_url('/icons/icon-192.png'), 'sizes' => '192x192']],
                ],
            ],
        ];

        header('Content-Type: application/manifest+json; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');

        echo json_encode($manifest, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }

    public function offline(): void
    {
        http_response_code(200);
        header('X-Robots-Tag: noindex');
        header('Cache-Control: public, max-age=60');

        $this->view('offline', [
            'title' => 'Tidak ada koneksi',
            'metaDescription' => 'Halaman cadangan saat perangkat sedang offline.',
        ]);
    }
}
