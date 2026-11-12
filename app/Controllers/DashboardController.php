<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\ActivityLog;
use App\Models\Profile;
use App\Models\Project;
use App\Models\Skill;
use App\Validator;
use App\Models\User;

final class DashboardController extends Controller
{
    public function index(): void
    {
        Auth::requireLogin();

        $user = Auth::user();
        $profile = Profile::get();

        $this->view('user/dashboard', [
            'title' => 'Portofolio',
            'profile' => $profile,
            'user' => $user,
            'projects' => Project::published(60),
            'skills' => Skill::grouped(),
            'stats' => Project::stats(),
            'gallery' => Project::galleryImages(6),
        ], 'layouts/app');
    }

    public function profile(): void
    {
        Auth::requireLogin();

        $this->view('user/account', [
            'title' => 'Akun Saya',
            'user' => Auth::user(),
        ], 'layouts/app');
    }

    public function updatePassword(): void
    {
        Auth::requireLogin();
        $sessionUser = Auth::user();

        if (!$sessionUser) {
            $this->redirect('/login');
        }

        // Auth::user() tidak memuat password_hash, jadi ambil data lengkap.
        $user = User::find((int) $sessionUser['id']) ?? $sessionUser;

        $current = (string) $this->input('current_password');
        $password = (string) $this->input('password');
        $confirmation = (string) $this->input('password_confirmation');

        if (!password_verify($current, (string) $user['password_hash'])) {
            $this->backWithErrors(['current_password' => 'Kata sandi saat ini tidak cocok.'], [], '/profil');
        }

        $validator = new Validator(
            ['password' => $password, 'password_confirmation' => $confirmation],
            ['password' => 'required|min:8|max:200|confirmed'],
            ['password' => 'Kata sandi baru']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), [], '/profil');
        }

        User::updatePassword((int) $user['id'], $password);
        ActivityLog::record('password_changed', 'Mengubah kata sandi akun', (int) $user['id'], (string) $user['email']);
        \flash('success', 'Kata sandi berhasil diperbarui.');
        $this->redirect('/profil');
    }
}
