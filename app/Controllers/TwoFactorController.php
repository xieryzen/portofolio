<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\ActivityLog;
use App\Models\User;
use App\Totp;

/**
 * Langkah kedua saat login untuk akun yang mengaktifkan 2FA.
 */
final class TwoFactorController extends Controller
{
    private const MAX_TRIES = 6;
    private const EXPIRE_SECONDS = 600;

    public function show(): void
    {
        if (Auth::check()) {
            $this->redirect(Auth::isAdmin() ? '/admin' : '/dashboard');
        }

        if ($this->pendingUser() === null) {
            \flash('info', 'Sesi verifikasi sudah berakhir. Silakan masuk kembali.');
            $this->redirect('/login');
        }

        $this->view('auth/two-factor', [
            'title' => 'Verifikasi dua langkah',
            'metaDescription' => 'Masukkan kode dari aplikasi authenticator.',
        ], 'layouts/auth');
    }

    public function verify(): void
    {
        $user = $this->pendingUser();

        if ($user === null) {
            \flash('info', 'Sesi verifikasi sudah berakhir. Silakan masuk kembali.');
            $this->redirect('/login');
        }

        $tries = (int) ($_SESSION['_2fa_tries'] ?? 0) + 1;
        $_SESSION['_2fa_tries'] = $tries;

        if ($tries > self::MAX_TRIES) {
            $this->forget();
            ActivityLog::record('2fa_blocked', 'Terlalu banyak kode salah', (int) $user['id'], (string) $user['email']);
            \flash('error', 'Terlalu banyak percobaan. Silakan masuk kembali.');
            $this->redirect('/login');
        }

        $code = $this->inputString('code');
        $secret = (string) ($user['totp_secret'] ?? '');
        $nextRecovery = null;

        $valid = $secret !== '' && Totp::verify($secret, $code);

        if (!$valid) {
            $nextRecovery = Totp::consumeRecoveryCode((string) ($user['totp_recovery'] ?? ''), $code);
            $valid = $nextRecovery !== null;
        }

        if (!$valid) {
            \flash('error', 'Kode tidak valid. Periksa jam perangkat dan coba lagi.');
            $this->redirect('/two-factor');
        }

        if ($nextRecovery !== null) {
            User::setTotpRecovery((int) $user['id'], $nextRecovery);
        }

        $next = (string) ($_SESSION['_2fa_next'] ?? '');
        $this->forget();

        Auth::clearAttempts((string) $user['email']);
        Auth::login($user, 'login_2fa');
        $this->clearFormState();

        if ($nextRecovery !== null) {
            ActivityLog::record('recovery_used', 'Masuk memakai kode pemulihan', (int) $user['id'], (string) $user['email']);
            \flash('info', 'Kode pemulihan terpakai dan tidak bisa digunakan lagi. Sisa kode: ' . Totp::remainingRecoveryCodes($nextRecovery) . '.');
        }

        \flash('success', 'Verifikasi berhasil. Selamat datang, ' . $user['name'] . '!');

        $fallback = ($user['role'] ?? '') === 'user' ? '/dashboard' : '/admin';
        $target = $next !== '' && str_starts_with($next, '/') ? $next : $fallback;
        $this->redirect($target);
    }

    public function cancel(): void
    {
        $this->forget();
        \flash('info', 'Verifikasi dibatalkan.');
        $this->redirect('/login');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function pendingUser(): ?array
    {
        $id = (int) ($_SESSION['_2fa_user'] ?? 0);
        $startedAt = (int) ($_SESSION['_2fa_at'] ?? 0);

        if ($id <= 0 || $startedAt <= 0 || (time() - $startedAt) > self::EXPIRE_SECONDS) {
            $this->forget();

            return null;
        }

        $user = User::find($id);

        if ($user === null || (int) $user['is_active'] !== 1 || (string) ($user['totp_enabled_at'] ?? '') === '') {
            $this->forget();

            return null;
        }

        return $user;
    }

    private function forget(): void
    {
        unset($_SESSION['_2fa_user'], $_SESSION['_2fa_next'], $_SESSION['_2fa_at'], $_SESSION['_2fa_tries']);
    }
}
