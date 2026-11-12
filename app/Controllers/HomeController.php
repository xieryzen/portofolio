<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;

final class HomeController extends Controller
{
    public function index(): void
    {
        $profile = Profile::get();
        $projects = Project::published(60);
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
            'metaDescription' => setting('meta_description', ''),
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

        $this->view('project', [
            'title' => (string) $project['title'],
            'project' => $project,
            'images' => Project::images((int) $project['id']),
            'related' => Project::related((int) $project['id'], 3),
            'profile' => Profile::get(),
            'metaDescription' => (string) $project['summary'],
        ]);
    }
}
