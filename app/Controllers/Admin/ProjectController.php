<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Project;
use App\Models\Release;
use App\Uploader;
use App\Validator;

final class ProjectController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $status = $this->inputString('status');
        if (!in_array($status, ['published', 'draft'], true)) {
            $status = '';
        }

        $search = $this->inputString('q');
        $page = \query_int('page', 1);

        $this->view('admin/projects/index', [
            'title' => 'Kelola Portofolio',
            'result' => Project::paginate($page, 8, $status, $search),
            'status' => $status,
            'search' => $search,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/projects/form', [
            'title' => 'Tambah Project',
            'project' => null,
            'images' => [],
            'releases' => [],
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $data = $this->formData();
        $validator = $this->validate($data);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/projects/create');
        }

        $id = Project::create($data);

        if (isset($_FILES['cover']) && is_array($_FILES['cover']) && (int) $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = Uploader::image($_FILES['cover'], 'cover');
            if ($result['ok']) {
                Project::setCover($id, $result['file'] ?? null);
            } else {
                \flash('error', $result['error'] ?? 'Cover gagal diunggah.');
            }
        }

        $this->clearFormState();
        \flash('success', 'Project berhasil ditambahkan.');
        $this->redirect('/admin/projects/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if (!$project) {
            \flash('error', 'Project tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $this->view('admin/projects/form', [
            'title' => 'Edit Project',
            'project' => $project,
            'images' => Project::images((int) $project['id']),
            'releases' => Release::forProject((int) $project['id']),
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if (!$project) {
            \flash('error', 'Project tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $data = $this->formData();
        $validator = $this->validate($data);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/projects/' . $id . '/edit');
        }

        Project::update((int) $project['id'], $data);
        $this->clearFormState();
        \flash('success', 'Project berhasil diperbarui.');
        $this->redirect('/admin/projects/' . $project['id'] . '/edit');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if (!$project) {
            \flash('error', 'Project tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        Uploader::delete($project['cover'] !== null ? (string) $project['cover'] : null);
        Project::delete((int) $project['id']);

        \flash('success', 'Project "' . $project['title'] . '" berhasil dihapus.');
        $this->redirect('/admin/projects');
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if ($project) {
            $newStatus = $project['status'] === 'published' ? 'draft' : 'published';
            Project::setStatus((int) $project['id'], $newStatus);
            \flash('success', $newStatus === 'published' ? 'Project dipublikasikan.' : 'Project disimpan sebagai draft.');
        }

        $this->redirect('/admin/projects');
    }

    public function uploadCover(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        $file = $_FILES['cover'] ?? null;

        if (!$project || !is_array($file)) {
            \flash('error', 'Gagal mengunggah cover.');
            $this->redirect('/admin/projects');
        }

        $result = Uploader::image($file, 'cover');
        if (!$result['ok']) {
            \flash('error', $result['error'] ?? 'Gagal mengunggah cover.');
            $this->redirect('/admin/projects/' . $id . '/edit');
        }

        if ($project['cover']) {
            Uploader::delete((string) $project['cover']);
        }
        Project::setCover((int) $project['id'], $result['file'] ?? null);
        \flash('success', 'Cover project berhasil diperbarui.');
        $this->redirect('/admin/projects/' . $id . '/edit');
    }

    public function removeCover(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if ($project && $project['cover']) {
            Uploader::delete((string) $project['cover']);
            Project::setCover((int) $project['id'], null);
            \flash('success', 'Cover project dihapus.');
        }

        $this->redirect('/admin/projects/' . $id . '/edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        return [
            'title' => $this->inputString('title'),
            'summary' => $this->inputString('summary'),
            'description' => $this->cleanText($this->inputString('description'), 20000),
            'category' => $this->inputString('category', 'Web App'),
            'client' => $this->inputString('client'),
            'role' => $this->inputString('role'),
            'year' => $this->inputString('year'),
            'tech_stack' => $this->inputString('tech_stack'),
            'case_challenge' => $this->cleanText($this->inputString('case_challenge'), 4000),
            'case_solution' => $this->cleanText($this->inputString('case_solution'), 4000),
            'case_result' => $this->cleanText($this->inputString('case_result'), 4000),
            'case_metrics' => $this->cleanText($this->inputString('case_metrics'), 1200),
            'title_en' => $this->inputString('title_en'),
            'summary_en' => $this->inputString('summary_en'),
            'description_en' => $this->cleanText($this->inputString('description_en'), 20000),
            'case_challenge_en' => $this->cleanText($this->inputString('case_challenge_en'), 4000),
            'case_solution_en' => $this->cleanText($this->inputString('case_solution_en'), 4000),
            'case_result_en' => $this->cleanText($this->inputString('case_result_en'), 4000),
            'project_url' => $this->inputString('project_url'),
            'repo_url' => $this->inputString('repo_url'),
            'is_featured' => $this->input('is_featured') === '1' ? 1 : 0,
            'status' => $this->inputString('status') === 'draft' ? 'draft' : 'published',
            'sort_order' => (int) $this->input('sort_order', 0),
        ];
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validate(array $data): Validator
    {
        return new Validator(
            $data,
            [
                'title' => 'required|min:3|max:180',
                'summary' => 'max:300',
                'category' => 'max:80',
                'client' => 'max:150',
                'role' => 'max:150',
                'year' => 'max:10',
                'tech_stack' => 'max:300',
                'title_en' => 'max:180',
                'summary_en' => 'max:300',
                'project_url' => 'url|max:255',
                'repo_url' => 'url|max:255',
            ],
            [
                'title' => 'Judul project',
                'summary' => 'Ringkasan',
                'tech_stack' => 'Tech stack',
                'title_en' => 'Judul (EN)',
                'summary_en' => 'Ringkasan (EN)',
                'project_url' => 'URL project',
                'repo_url' => 'URL repository',
            ]
        );
    }
}
