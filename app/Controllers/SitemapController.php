<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\Project;

/**
 * sitemap.xml dan robots.txt dinamis, memakai domain dari request saat ini.
 */
final class SitemapController extends Controller
{
    public function index(): void
    {
        $projects = Project::published(500);
        $posts = Post::latest(500);

        $urls = [[
            'loc' => absolute_url('/'),
            'lastmod' => date('Y-m-d'),
            'priority' => '1.0',
            'changefreq' => 'weekly',
        ]];

        if ($posts !== []) {
            $urls[] = [
                'loc' => absolute_url('/blog'),
                'lastmod' => date('Y-m-d'),
                'priority' => '0.7',
                'changefreq' => 'weekly',
            ];
        }

        foreach ($projects as $project) {
            $updated = (string) ($project['updated_at'] ?? '');

            $urls[] = [
                'loc' => absolute_url('/project/' . $project['slug']),
                'lastmod' => $updated !== '' ? substr($updated, 0, 10) : date('Y-m-d'),
                'priority' => '0.8',
                'changefreq' => 'monthly',
            ];
        }

        foreach ($posts as $post) {
            $updated = (string) ($post['updated_at'] ?? '');

            $urls[] = [
                'loc' => absolute_url('/blog/' . $post['slug']),
                'lastmod' => $updated !== '' ? substr($updated, 0, 10) : date('Y-m-d'),
                'priority' => '0.6',
                'changefreq' => 'monthly',
            ];
        }

        header('Content-Type: application/xml; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($urls as $url) {
            echo "  <url>\n";
            echo '    <loc>' . htmlspecialchars($url['loc'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</loc>\n";
            echo '    <lastmod>' . htmlspecialchars($url['lastmod'], ENT_XML1) . "</lastmod>\n";
            echo '    <changefreq>' . $url['changefreq'] . "</changefreq>\n";
            echo '    <priority>' . $url['priority'] . "</priority>\n";
            echo "  </url>\n";
        }

        echo '</urlset>' . "\n";
    }

    public function robots(): void
    {
        header('Content-Type: text/plain; charset=UTF-8');
        header('Cache-Control: public, max-age=3600');

        $lines = [
            'User-agent: *',
            'Allow: /',
            'Disallow: /admin',
            'Disallow: /login',
            'Disallow: /register',
            'Disallow: /profil',
            'Disallow: /dashboard',
            'Disallow: /download/',
            '',
            'Sitemap: ' . absolute_url('/sitemap.xml'),
            '',
        ];

        echo implode("\n", $lines);
    }
}
