<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Post;
use App\Uploader;
use App\Validator;

final class PostController extends Controller
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

        $this->view('admin/posts/index', [
            'title' => 'Kelola artikel',
            'result' => Post::paginateAdmin($page, 10, $status, $search),
            'status' => $status,
            'search' => $search,
        ], 'layouts/admin');
    }

    public function create(): void
    {
        Auth::requireAdmin();

        $this->view('admin/posts/form', [
            'title' => 'Tulis artikel',
            'post' => null,
        ], 'layouts/admin');
    }

    public function store(): void
    {
        Auth::requireAdmin();

        $data = $this->formData();
        $validator = $this->validate($data);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/posts/create');
        }

        $id = Post::create($data);

        if (isset($_FILES['cover']) && is_array($_FILES['cover']) && (int) $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = Uploader::image($_FILES['cover'], 'post');
            if ($result['ok']) {
                Post::setCover($id, $result['file'] ?? null);
            } else {
                \flash('error', $result['error'] ?? 'Cover gagal diunggah.');
            }
        }

        ActivityLog::record('post_created', 'Menulis artikel: ' . $data['title']);
        $this->clearFormState();
        \flash('success', 'Artikel berhasil disimpan.');
        $this->redirect('/admin/posts/' . $id . '/edit');
    }

    public function edit(string $id): void
    {
        Auth::requireAdmin();

        $post = Post::find((int) $id);
        if ($post === null) {
            \flash('error', 'Artikel tidak ditemukan.');
            $this->redirect('/admin/posts');
        }

        $this->view('admin/posts/form', [
            'title' => 'Edit artikel',
            'post' => $post,
        ], 'layouts/admin');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        $post = Post::find((int) $id);
        if ($post === null) {
            \flash('error', 'Artikel tidak ditemukan.');
            $this->redirect('/admin/posts');
        }

        $data = $this->formData();
        $validator = $this->validate($data);

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/posts/' . $id . '/edit');
        }

        Post::update((int) $post['id'], $data);

        if (isset($_FILES['cover']) && is_array($_FILES['cover']) && (int) $_FILES['cover']['error'] !== UPLOAD_ERR_NO_FILE) {
            $result = Uploader::image($_FILES['cover'], 'post');
            if ($result['ok']) {
                $old = $post['cover'] ?? null;
                Post::setCover((int) $post['id'], $result['file'] ?? null);
                if ($old) {
                    Uploader::delete((string) $old);
                }
            } else {
                \flash('error', $result['error'] ?? 'Cover gagal diunggah.');
            }
        }

        ActivityLog::record('post_updated', 'Memperbarui artikel: ' . $data['title']);
        $this->clearFormState();
        \flash('success', 'Artikel berhasil diperbarui.');
        $this->redirect('/admin/posts/' . $post['id'] . '/edit');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $post = Post::find((int) $id);
        if ($post === null) {
            \flash('error', 'Artikel tidak ditemukan.');
            $this->redirect('/admin/posts');
        }

        if (!empty($post['cover'])) {
            Uploader::delete((string) $post['cover']);
        }

        Post::delete((int) $post['id']);
        ActivityLog::record('post_deleted', 'Menghapus artikel: ' . $post['title']);
        \flash('success', 'Artikel dihapus.');
        $this->redirect('/admin/posts');
    }

    public function removeCover(string $id): void
    {
        Auth::requireAdmin();

        $post = Post::find((int) $id);
        if ($post !== null && !empty($post['cover'])) {
            Uploader::delete((string) $post['cover']);
            Post::setCover((int) $post['id'], null);
            \flash('success', 'Cover artikel dihapus.');
        }

        $this->redirect('/admin/posts/' . $id . '/edit');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $content = $this->cleanText($this->inputString('content'), 60000);

        return [
            'title' => $this->inputString('title'),
            'excerpt' => $this->inputString('excerpt'),
            'content' => $content,
            'category' => $this->inputString('category', 'Artikel') ?: 'Artikel',
            'tags' => $this->inputString('tags'),
            'status' => $this->inputString('status') === 'published' ? 'published' : 'draft',
            'is_featured' => $this->input('is_featured') === '1' ? 1 : 0,
            'reading_minutes' => Post::readingMinutes($content),
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
                'excerpt' => 'max:300',
                'category' => 'max:80',
                'tags' => 'max:200',
            ],
            [
                'title' => 'Judul artikel',
                'excerpt' => 'Ringkasan',
                'category' => 'Kategori',
                'tags' => 'Tag',
            ]
        );
    }
}
