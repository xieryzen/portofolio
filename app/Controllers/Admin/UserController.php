<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\User;

final class UserController extends Controller
{
    public function index(): void
    {
        Auth::requireOwner();

        $this->view('admin/users', [
            'title' => 'Pengguna',
            'result' => User::paginate(\query_int('page', 1), 12, $this->inputString('q')),
            'search' => $this->inputString('q'),
            'stats' => User::stats(),
        ], 'layouts/admin');
    }

    public function updateRole(string $id): void
    {
        Auth::requireOwner();

        $currentUser = Auth::user();
        $user = User::find((int) $id);

        if (!$user) {
            \flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/admin/users');
        }

        if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0)) {
            \flash('error', 'Anda tidak dapat mengubah role akun sendiri.');
            $this->redirect('/admin/users');
        }

        if ($user['role'] === 'owner') {
            \flash('error', 'Role owner tidak dapat diubah.');
            $this->redirect('/admin/users');
        }

        $role = $this->inputString('role');
        if (!in_array($role, ['admin', 'user'], true)) {
            \flash('error', 'Role tidak valid.');
            $this->redirect('/admin/users');
        }

        User::setRole((int) $user['id'], $role);
        \flash('success', 'Role ' . $user['name'] . ' diubah menjadi ' . $role . '.');
        $this->redirect('/admin/users');
    }

    public function toggleStatus(string $id): void
    {
        Auth::requireOwner();

        $currentUser = Auth::user();
        $user = User::find((int) $id);

        if (!$user) {
            \flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/admin/users');
        }

        if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0) || $user['role'] === 'owner') {
            \flash('error', 'Akun ini tidak dapat dinonaktifkan.');
            $this->redirect('/admin/users');
        }

        $newState = (int) $user['is_active'] === 1 ? false : true;
        User::setActive((int) $user['id'], $newState);
        \flash('success', $newState ? 'Akun diaktifkan kembali.' : 'Akun dinonaktifkan.');
        $this->redirect('/admin/users');
    }

    public function destroy(string $id): void
    {
        Auth::requireOwner();

        $currentUser = Auth::user();
        $user = User::find((int) $id);

        if (!$user) {
            \flash('error', 'Pengguna tidak ditemukan.');
            $this->redirect('/admin/users');
        }

        if ((int) $user['id'] === (int) ($currentUser['id'] ?? 0) || $user['role'] === 'owner') {
            \flash('error', 'Akun ini tidak dapat dihapus.');
            $this->redirect('/admin/users');
        }

        User::delete((int) $user['id']);
        \flash('success', 'Akun ' . $user['name'] . ' dihapus.');
        $this->redirect('/admin/users');
    }
}
