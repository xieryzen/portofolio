<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\User;
use App\Validator;

final class AuthController extends Controller
{
    public function showLogin(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
        }

        $this->view('auth/login', [
            'title' => 'Masuk',
            'metaDescription' => 'Masuk ke area anggota.',
            'next' => $this->safeNext($this->inputString('next')),
        ], 'layouts/auth');
    }

    public function login(): void
    {
        $email = mb_strtolower($this->inputString('email'));
        $password = (string) $this->input('password');
        $next = $this->safeNext($this->inputString('next'));

        $validator = new Validator(
            ['email' => $email, 'password' => $password],
            ['email' => 'required|email', 'password' => 'required|min:6']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), ['email' => $email], '/login' . ($next ? '?next=' . urlencode($next) : ''));
        }

        if (Auth::tooManyAttempts($email)) {
            \flash('error', 'Terlalu banyak percobaan masuk. Silakan coba lagi dalam 10 menit.');
            \redirect('/login');
        }

        $user = User::findByEmail($email);

        if (!$user || !password_verify($password, (string) $user['password_hash'])) {
            Auth::recordAttempt($email);
            $this->backWithErrors(
                ['email' => 'Email atau kata sandi salah.'],
                ['email' => $email],
                '/login'
            );
        }

        if ((int) $user['is_active'] !== 1) {
            \flash('error', 'Akun Anda sedang dinonaktifkan. Hubungi pemilik situs.');
            \redirect('/login');
        }

        if (password_needs_rehash((string) $user['password_hash'], PASSWORD_DEFAULT)) {
            User::updatePassword((int) $user['id'], $password);
        }

        Auth::clearAttempts($email);
        Auth::login($user);
        $this->clearFormState();

        \flash('success', 'Selamat datang kembali, ' . $user['name'] . '!');

        $target = $next ?: (in_array($user['role'], ['owner', 'admin'], true) ? '/admin' : '/dashboard');
        $this->redirect($target);
    }

    public function showRegister(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
        }

        if (!registration_open()) {
            \flash('info', 'Pendaftaran akun sedang ditutup.');
            \redirect('/login');
        }

        $this->view('auth/register', [
            'title' => 'Daftar',
            'metaDescription' => 'Buat akun untuk mengakses portofolio.',
        ], 'layouts/auth');
    }

    public function register(): void
    {
        if (!registration_open()) {
            \flash('info', 'Pendaftaran akun sedang ditutup.');
            \redirect('/login');
        }

        $data = [
            'name' => $this->inputString('name'),
            'email' => mb_strtolower($this->inputString('email')),
            'password' => (string) $this->input('password'),
            'password_confirmation' => (string) $this->input('password_confirmation'),
        ];

        $validator = new Validator(
            $data,
            [
                'name' => 'required|min:3|max:100|alpha_dash',
                'email' => 'required|email|max:190|unique:users,email',
                'password' => 'required|min:8|max:200|confirmed',
            ],
            ['name' => 'Nama', 'email' => 'Email', 'password' => 'Kata sandi']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), ['name' => $data['name'], 'email' => $data['email']], '/register');
        }

        $id = User::create($data['name'], $data['email'], $data['password'], 'user');
        $user = User::find($id);

        if (!$user) {
            \flash('error', 'Pendaftaran gagal, silakan coba lagi.');
            \redirect('/register');
        }

        Auth::login($user);
        $this->clearFormState();
        \flash('success', 'Akun berhasil dibuat. Selamat datang!');
        $this->redirect('/dashboard');
    }

    public function logout(): void
    {
        Auth::logout();
        \flash('success', 'Anda telah keluar.');
        $this->redirect('/');
    }

    private function safeNext(string $next): string
    {
        if (
            $next === ''
            || !str_starts_with($next, '/')
            || str_starts_with($next, '//')
            || str_contains($next, '\\')
            || preg_match('~[\x00-\x1F\x7F]~', $next)
        ) {
            return '';
        }

        return $next;
    }
}
