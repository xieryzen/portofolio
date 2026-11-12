<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;

/**
 * RSS 2.0 untuk artikel blog.
 */
final class FeedController extends Controller
{
    public function index(): void
    {
        $posts = Post::latest(20);
        $siteTitle = setting('site_title', 'Portfolio');
        $description = setting('meta_description', 'Artikel terbaru dari ' . $siteTitle);

        header('Content-Type: application/rss+xml; charset=UTF-8');
        header('Cache-Control: public, max-age=1800');

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">' . "\n";
        echo "  <channel>\n";
        echo '    <title>' . htmlspecialchars($siteTitle, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</title>\n";
        echo '    <link>' . htmlspecialchars(absolute_url('/blog'), ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</link>\n";
        echo '    <description>' . htmlspecialchars($description, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</description>\n";
        echo '    <language>' . (\App\Lang::locale() === 'en' ? 'en' : 'id') . "</language>\n";
        echo '    <lastBuildDate>' . date(DATE_RSS) . "</lastBuildDate>\n";
        echo '    <atom:link href="' . htmlspecialchars(absolute_url('/feed.xml'), ENT_XML1 | ENT_QUOTES, 'UTF-8') . '" rel="self" type="application/rss+xml" />' . "\n";

        foreach ($posts as $post) {
            $url = absolute_url('/blog/' . $post['slug']);
            $published = (string) ($post['published_at'] ?? $post['created_at']);
            $timestamp = strtotime($published) ?: time();

            echo "    <item>\n";
            echo '      <title>' . htmlspecialchars((string) $post['title'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</title>\n";
            echo '      <link>' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</link>\n";
            echo '      <guid isPermaLink="true">' . htmlspecialchars($url, ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</guid>\n";
            echo '      <pubDate>' . date(DATE_RSS, $timestamp) . "</pubDate>\n";
            echo '      <category>' . htmlspecialchars((string) $post['category'], ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</category>\n";
            echo '      <description>' . htmlspecialchars((string) ($post['excerpt'] !== '' ? $post['excerpt'] : excerpt((string) $post['content'], 200)), ENT_XML1 | ENT_QUOTES, 'UTF-8') . "</description>\n";
            echo "    </item>\n";
        }

        echo "  </channel>\n";
        echo "</rss>\n";
    }
}
