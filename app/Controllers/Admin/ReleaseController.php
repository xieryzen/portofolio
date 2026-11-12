<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\Project;
use App\Models\Release;
use App\Uploader;
use App\Validator;

final class ReleaseController extends Controller
{
    public function store(string $projectId): void
    {
        Auth::requireAdmin();

        $project = Project::find((int) $projectId);
        if (!$project) {
            \flash('error', 'Project tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $data = $this->formData();

        $validator = new Validator(
            $data,
            ['version' => 'required|max:40', 'title' => 'max:150', 'notes' => 'max:20000'],
            ['version' => 'Versi', 'title' => 'Judul rilis', 'notes' => 'Catatan rilis']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/projects/' . $project['id'] . '/edit');
        }

        if (Release::versionExists((int) $project['id'], $data['version'])) {
            \flash('error', 'Versi ' . $data['version'] . ' sudah ada untuk project ini. Gunakan nomor versi lain.');
            $this->redirect('/admin/projects/' . $project['id'] . '/edit#rilis');
        }

        $file = $_FILES['file'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = Uploader::release($file);
            if (!$upload['ok']) {
                \flash('error', $upload['error'] ?? 'Gagal mengunggah file rilis.');
                $this->redirect('/admin/projects/' . $project['id'] . '/edit#rilis');
            }

            $data['file_name'] = $upload['file'] ?? null;
            $data['original_name'] = $upload['original_name'] ?? null;
            $data['file_size'] = $upload['size'] ?? 0;
        }

        Release::create((int) $project['id'], $data);

        $this->clearFormState();
        \flash('success', 'Rilis ' . $data['version'] . ' berhasil ditambahkan.');
        $this->redirect('/admin/projects/' . $project['id'] . '/edit#rilis');
    }

    public function update(string $id): void
    {
        Auth::requireAdmin();

        $release = Release::find((int) $id);
        if (!$release) {
            \flash('error', 'Rilis tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        $projectId = (int) $release['project_id'];
        $data = $this->formData();

        $validator = new Validator(
            $data,
            ['version' => 'required|max:40', 'title' => 'max:150', 'notes' => 'max:20000'],
            ['version' => 'Versi', 'title' => 'Judul rilis', 'notes' => 'Catatan rilis']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/projects/' . $projectId . '/edit#rilis');
        }

        if (Release::versionExists($projectId, $data['version'], (int) $release['id'])) {
            \flash('error', 'Versi ' . $data['version'] . ' sudah dipakai rilis lain pada project ini.');
            $this->redirect('/admin/projects/' . $projectId . '/edit#rilis');
        }

        $file = $_FILES['file'] ?? null;
        if (is_array($file) && (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
            $upload = Uploader::release($file);
            if (!$upload['ok']) {
                \flash('error', $upload['error'] ?? 'Gagal mengunggah file rilis.');
                $this->redirect('/admin/projects/' . $projectId . '/edit#rilis');
            }

            Release::replaceFile(
                (int) $release['id'],
                (string) ($upload['file'] ?? ''),
                (string) ($upload['original_name'] ?? ''),
                (int) ($upload['size'] ?? 0)
            );
        }

        Release::update((int) $release['id'], $data);

        $this->clearFormState();
        \flash('success', 'Rilis ' . $data['version'] . ' berhasil diperbarui.');
        $this->redirect('/admin/projects/' . $projectId . '/edit#rilis');
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireAdmin();

        $release = Release::find((int) $id);
        if ($release) {
            $publish = (int) $release['is_published'] !== 1;
            Release::setPublished((int) $release['id'], $publish);
            \flash('success', $publish ? 'Rilis dipublikasikan.' : 'Rilis disembunyikan dari pengunjung.');
            $this->redirect('/admin/projects/' . $release['project_id'] . '/edit#rilis');
        }

        $this->redirect('/admin/projects');
    }

    public function removeFile(string $id): void
    {
        Auth::requireAdmin();

        $release = Release::find((int) $id);
        if ($release) {
            Release::removeFile((int) $release['id']);
            \flash('success', 'File rilis dihapus. Rilis tetap ada sebagai catatan versi.');
            $this->redirect('/admin/projects/' . $release['project_id'] . '/edit#rilis');
        }

        $this->redirect('/admin/projects');
    }

    public function destroy(string $id): void
    {
        Auth::requireAdmin();

        $release = Release::find((int) $id);
        if (!$release) {
            \flash('error', 'Rilis tidak ditemukan.');
            $this->redirect('/admin/projects');
        }

        Release::delete((int) $release['id']);
        \flash('success', 'Rilis ' . $release['version'] . ' beserta filenya telah dihapus.');
        $this->redirect('/admin/projects/' . $release['project_id'] . '/edit#rilis');
    }

    /**
     * @return array<string, mixed>
     */
    private function formData(): array
    {
        $releasedAt = $this->inputString('released_at');
        $timestamp = $releasedAt !== '' ? strtotime($releasedAt) : false;

        return [
            'version' => mb_substr($this->inputString('version'), 0, 40, 'UTF-8'),
            'title' => mb_substr($this->inputString('title'), 0, 150, 'UTF-8'),
            'notes' => $this->cleanText($this->inputString('notes'), 20000),
            'released_at' => $timestamp !== false ? date('Y-m-d', $timestamp) : date('Y-m-d'),
            'is_published' => $this->input('is_published') === '1' ? 1 : 0,
        ];
    }
}
