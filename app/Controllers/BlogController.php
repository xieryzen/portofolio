<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Comment;
use App\Models\Post;
use App\Models\Profile;
use App\Visits;

final class BlogController extends Controller
{
    public function index(): void
    {
        Visits::record('/blog');

        $page = max(1, (int) ($_GET['page'] ?? 1));
        $category = trim((string) ($_GET['kategori'] ?? ''));
        $search = trim((string) ($_GET['q'] ?? ''));
        $search = mb_substr(preg_replace('~\s+~u', ' ', $search) ?? '', 0, 80, 'UTF-8');

        $result = Post::paginatePublic($page, 9, $category, $search);

        $this->view('blog/index', [
            'title' => 'Artikel & catatan',
            'metaDescription' => 'Tulisan seputar pengembangan web, proses kerja, dan hal teknis yang saya pelajari.',
            'result' => $result,
            'categories' => Post::categories(),
            'category' => $category,
            'searchTerm' => $search,
            'profile' => Profile::get(),
        ]);
    }

    public function show(string $slug): void
    {
        $post = Post::findBySlug($slug);

        if ($post === null) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Artikel tidak ditemukan']);

            return;
        }

        Post::incrementViews((int) $post['id']);
        Visits::record('/blog/' . $post['slug']);

        $this->view('blog/show', [
            'title' => \localized($post, 'title'),
            'metaDescription' => \localized($post, 'excerpt') !== ''
                ? \localized($post, 'excerpt')
                : excerpt(\localized($post, 'content'), 160),
            'post' => $post,
            'related' => Post::related((int) $post['id'], (string) $post['category'], 3),
            'comments' => Comment::approvedFor((int) $post['id']),
            'commentCount' => Comment::countForPost((int) $post['id']),
            'profile' => Profile::get(),
        ]);
    }
}
