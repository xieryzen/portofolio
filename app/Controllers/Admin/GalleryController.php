<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Project;
use App\Uploader;

final class GalleryController extends Controller
{
    public function store(string $id): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $id);
        if (!$project) {
            \flash('error', 'Project tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $files = $_FILES['images'] ?? null;
        if (!is_array($files) || !isset($files['name'])) {
            \flash('error', 'Pilih minimal satu gambar dokumentasi.');
            $this->redirect('/admin/projects/' . $id . '/edit');
        }

        $captions = $_POST['captions'] ?? [];
        $names = (array) $files['name'];
        $uploaded = 0;
        $failed = [];

        foreach (array_keys($names) as $index) {
            $single = [
                'name' => $files['name'][$index] ?? '',
                'type' => $files['type'][$index] ?? '',
                'tmp_name' => $files['tmp_name'][$index] ?? '',
                'error' => $files['error'][$index] ?? UPLOAD_ERR_NO_FILE,
                'size' => $files['size'][$index] ?? 0,
            ];

            if ((int) $single['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }

            $result = Uploader::image($single, 'doc');
            if ($result['ok']) {
                $caption = is_array($captions) ? trim((string) ($captions[$index] ?? '')) : '';
                Project::addImage((int) $project['id'], (string) $result['file'], mb_substr($caption, 0, 200, 'UTF-8'));
                $uploaded++;
            } else {
                $failed[] = $result['error'] ?? 'Gagal mengunggah gambar.';
            }
        }

        if ($uploaded > 0) {
            \flash('success', $uploaded . ' gambar dokumentasi berhasil ditambahkan.');
        }

        if ($failed !== []) {
            \flash('error', implode(' ', array_unique($failed)));
        }

        $this->redirect('/admin/projects/' . $id . '/edit');
    }

    public function updateCaption(string $id): void
    {
        Auth::requireAdmin();

        $image = Project::findImage((int) $id);
        if (!$image) {
            \flash('error', 'Gambar tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $caption = mb_substr($this->inputString('caption'), 0, 200, 'UTF-8');
        Project::updateImageCaption((int) $image['id'], $caption);

        \flash('success', 'Keterangan gambar diperbarui.');
        $this->redirect('/admin/projects/' . $image['project_id'] . '/edit');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $image = Project::findImage((int) $id);
        if (!$image) {
            \flash('error', 'Gambar tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        Project::deleteImage((int) $image['id']);
        \flash('success', 'Gambar dokumentasi dihapus.');
        $this->redirect('/admin/projects/' . $image['project_id'] . '/edit');
    }
}
