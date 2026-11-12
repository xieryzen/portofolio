<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Profile;
use App\Settings;
use App\Uploader;
use App\Validator;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        Auth::requireAdmin();

        $this->view('admin/profile', [
            'title' => 'Profil Portofolio',
            'profile' => Profile::get(),
        ], 'layouts/admin');
    }

    public function update(): void
    {
        Auth::requireAdmin();

        $data = [
            'full_name' => $this->inputString('full_name'),
            'headline' => $this->inputString('headline'),
            'tagline' => $this->inputString('tagline'),
            'bio' => $this->cleanText($this->inputString('bio'), 8000),
            'location' => $this->inputString('location'),
            'public_email' => $this->inputString('public_email'),
            'phone' => $this->inputString('phone'),
            'availability' => $this->inputString('availability'),
            'years_experience' => (int) $this->input('years_experience', 0),
            'projects_completed' => (int) $this->input('projects_completed', 0),
            'clients_count' => (int) $this->input('clients_count', 0),
            'github' => $this->inputString('github'),
            'linkedin' => $this->inputString('linkedin'),
            'instagram' => $this->inputString('instagram'),
            'x' => $this->inputString('x'),
            'website' => $this->inputString('website'),
            'whatsapp' => $this->inputString('whatsapp'),
        ];

        $validator = new Validator(
            $data,
            [
                'full_name' => 'required|min:2|max:150',
                'headline' => 'max:200',
                'tagline' => 'max:300',
                'public_email' => 'email|max:190',
                'website' => 'url|max:200',
                'github' => 'url|max:200',
                'linkedin' => 'url|max:200',
                'instagram' => 'url|max:200',
                'x' => 'url|max:200',
            ],
            [
                'full_name' => 'Nama lengkap',
                'headline' => 'Headline',
                'tagline' => 'Tagline',
                'public_email' => 'Email publik',
                'website' => 'Website',
                'github' => 'GitHub',
                'linkedin' => 'LinkedIn',
                'instagram' => 'Instagram',
                'x' => 'X / Twitter',
            ]
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/profile');
        }

        Profile::update($data);
        $this->clearFormState();
        \flash('success', 'Profil berhasil diperbarui. Perubahan langsung tampil di halaman portofolio.');
        $this->redirect('/admin/profile');
    }

    public function uploadImage(): void
    {
        Auth::requireAdmin();

        $type = $this->inputString('type') === 'cover' ? 'cover' : 'avatar';
        $file = $_FILES['image'] ?? null;
        $profile = Profile::get();

        if (!is_array($file)) {
            \flash('error', 'Pilih gambar terlebih dahulu.');
            $this->redirect('/admin/profile');
        }

        $result = Uploader::image($file, $type);
        if (!$result['ok']) {
            \flash('error', $result['error'] ?? 'Gagal mengunggah gambar.');
            $this->redirect('/admin/profile');
        }

        $old = $profile[$type] ?? null;
        Profile::setImage($type, $result['file'] ?? null);

        if ($old && $old !== ($result['file'] ?? null)) {
            Uploader::delete((string) $old);
        }

        \flash('success', $type === 'cover' ? 'Foto sampul berhasil diperbarui.' : 'Foto profil berhasil diperbarui.');
        $this->redirect('/admin/profile');
    }

    public function uploadCv(): void
    {
        Auth::requireAdmin();

        $file = $_FILES['cv'] ?? null;

        if (!is_array($file)) {
            \flash('error', 'Pilih berkas CV terlebih dahulu.');
            $this->redirect('/admin/profile');
        }

        $result = Uploader::document($file);

        if (!$result['ok']) {
            \flash('error', $result['error'] ?? 'Gagal mengunggah CV.');
            $this->redirect('/admin/profile');
        }

        $old = setting('cv_file', '');
        Settings::set('cv_file', (string) ($result['file'] ?? ''));
        Settings::set('cv_original_name', (string) ($result['original_name'] ?? 'cv.pdf'));
        Settings::flush();

        if ($old !== '') {
            Uploader::deleteDocument($old);
        }

        ActivityLog::record('cv_updated', 'Mengunggah CV terbaru');
        \flash('success', 'CV berhasil diunggah. Tombol Unduh CV sekarang tampil di halaman publik.');
        $this->redirect('/admin/profile');
    }

    public function deleteCv(): void
    {
        Auth::requireAdmin();

        $old = setting('cv_file', '');

        if ($old !== '') {
            Uploader::deleteDocument($old);
        }

        Settings::set('cv_file', '');
        Settings::set('cv_original_name', '');
        Settings::flush();

        ActivityLog::record('cv_removed', 'Menghapus CV');
        \flash('success', 'CV dihapus. Tombol Unduh CV disembunyikan dari halaman publik.');
        $this->redirect('/admin/profile');
    }

    public function removeImage(): void
    {
        Auth::requireAdmin();

        $type = $this->inputString('type') === 'cover' ? 'cover' : 'avatar';
        $profile = Profile::get();
        $old = $profile[$type] ?? null;

        if ($old) {
            Uploader::delete((string) $old);
            Profile::setImage($type, null);
            \flash('success', 'Gambar berhasil dihapus.');
        }

        $this->redirect('/admin/profile');
    }
}
