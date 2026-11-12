<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Post;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Release;
use App\Models\Skill;
use App\Visits;

final class HomeController extends Controller
{
    public function index(): void
    {
        Visits::record('/');

        $profile = Profile::get();

        $searchTerm = trim((string) ($_GET['q'] ?? ''));
        $searchTerm = mb_substr(preg_replace('~\s+~u', ' ', $searchTerm) ?? '', 0, 80, 'UTF-8');

        $projects = $searchTerm !== ''
            ? Project::searchPublished($searchTerm, 60)
            : Project::published(60);
        $featured = array_values(array_filter($projects, static fn (array $p): bool => (int) $p['is_featured'] === 1));

        if ($featured === []) {
            $featured = array_slice($projects, 0, 2);
        }

        $stats = Project::stats();

        $this->view('home', [
            'title' => $profile['full_name'] !== '' ? $profile['full_name'] : (string) setting('site_title', 'Portfolio'),
            'profile' => $profile,
            'projects' => $projects,
            'featured' => $featured,
            'skills' => Skill::grouped(),
            'stats' => $stats,
            'gallery' => Project::galleryImages(8),
            'latestPosts' => Post::latest(3),
            'metaDescription' => setting('meta_description', ''),
            'searchTerm' => $searchTerm,
        ]);
    }

    public function show(string $slug): void
    {
        $project = Project::findBySlug($slug);

        if (!$project) {
            http_response_code(404);
            $this->view('errors/404', ['title' => 'Project tidak ditemukan']);

            return;
        }

        Project::incrementViews((int) $project['id']);
        Visits::record('/project/' . $project['slug']);

        $this->view('project', [
            'title' => (string) $project['title'],
            'project' => $project,
            'images' => Project::images((int) $project['id']),
            'releases' => Release::forProject((int) $project['id'], true),
            'related' => Project::related((int) $project['id'], 3),
            'profile' => Profile::get(),
            'metaDescription' => (string) $project['summary'],
        ]);
    }
}
