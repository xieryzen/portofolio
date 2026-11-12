<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\User;
use App\Totp;

/**
 * Pengaturan keamanan akun sendiri: 2FA (TOTP) dan kode pemulihan.
 */
final class SecurityController extends Controller
{
    public function index(): void
    {
        Auth::requireAdmin();

        $user = $this->currentUser();
        $enabled = (string) ($user['totp_enabled_at'] ?? '') !== '';
        $pending = $enabled ? null : (string) ($_SESSION['_totp_pending'] ?? '');

        $recoveryCodes = $_SESSION['_totp_recovery_plain'] ?? [];
        unset($_SESSION['_totp_recovery_plain']);

        $this->view('admin/security', [
            'title' => 'Keamanan akun',
            'user' => $user,
            'enabled' => $enabled,
            'pendingSecret' => $pending,
            'otpauthUri' => $pending !== null && $pending !== ''
                ? Totp::uri($pending, (string) $user['email'], (string) setting('site_title', 'Portfolio'))
                : null,
            'recoveryCodes' => is_array($recoveryCodes) ? $recoveryCodes : [],
            'remainingCodes' => Totp::remainingRecoveryCodes((string) ($user['totp_recovery'] ?? '')),
            'enabledAt' => (string) ($user['totp_enabled_at'] ?? ''),
        ], 'layouts/admin');
    }

    /**
     * Langkah 1: buat secret sementara (disimpan di sesi sampai dikonfirmasi).
     */
    public function setup(): void
    {
        Auth::requireAdmin();

        $user = $this->currentUser();

        if ((string) ($user['totp_enabled_at'] ?? '') !== '') {
            \flash('info', '2FA sudah aktif untuk akun ini.');
            $this->redirect('/admin/security');
        }

        $_SESSION['_totp_pending'] = Totp::generateSecret();

        \flash('success', 'Secret dibuat. Pindai/ masukkan ke aplikasi authenticator, lalu masukkan kodenya.');
        $this->redirect('/admin/security');
    }

    /**
     * Langkah 2: verifikasi kode 6 digit, aktifkan 2FA, tampilkan kode pemulihan.
     */
    public function enable(): void
    {
        Auth::requireAdmin();

        $user = $this->currentUser();
        $secret = (string) ($_SESSION['_totp_pending'] ?? '');
        $code = $this->inputString('code');

        if ($secret === '') {
            \flash('error', 'Secret belum dibuat. Mulai pengaturan 2FA terlebih dahulu.');
            $this->redirect('/admin/security');
        }

        if (!Totp::verify($secret, $code)) {
            \flash('error', 'Kode tidak cocok. Pastikan waktu perangkat akurat, lalu coba lagi.');
            $this->redirect('/admin/security');
        }

        $codes = Totp::generateRecoveryCodes();
        User::enableTotp((int) $user['id'], $secret, Totp::hashRecoveryCodes($codes));

        unset($_SESSION['_totp_pending']);
        $_SESSION['_totp_recovery_plain'] = $codes;

        ActivityLog::record('2fa_enabled', 'Mengaktifkan verifikasi dua langkah', (int) $user['id'], (string) $user['email']);
        \flash('success', '2FA aktif. Simpan kode pemulihan di tempat aman.');
        $this->redirect('/admin/security');
    }

    /**
     * Nonaktifkan 2FA — butuh kata sandi + kode TOTP/kode pemulihan.
     */
    public function disable(): void
    {
        Auth::requireAdmin();

        $user = $this->currentUser();
        $password = (string) $this->input('password');
        $code = $this->inputString('code');

        if (!password_verify($password, (string) $user['password_hash'])) {
            \flash('error', 'Kata sandi salah.');
            $this->redirect('/admin/security');
        }

        $secret = (string) ($user['totp_secret'] ?? '');
        $valid = $secret !== '' && Totp::verify($secret, $code);

        if (!$valid) {
            $valid = Totp::consumeRecoveryCode((string) ($user['totp_recovery'] ?? ''), $code) !== null;
        }

        if (!$valid) {
            \flash('error', 'Kode verifikasi salah.');
            $this->redirect('/admin/security');
        }

        User::disableTotp((int) $user['id']);
        unset($_SESSION['_totp_pending'], $_SESSION['_totp_recovery_plain']);

        ActivityLog::record('2fa_disabled', 'Menonaktifkan verifikasi dua langkah', (int) $user['id'], (string) $user['email']);
        \flash('success', '2FA dinonaktifkan.');
        $this->redirect('/admin/security');
    }

    /**
     * Buat ulang kode pemulihan (butuh kata sandi).
     */
    public function regenerate(): void
    {
        Auth::requireAdmin();

        $user = $this->currentUser();

        if ((string) ($user['totp_enabled_at'] ?? '') === '') {
            \flash('error', 'Aktifkan 2FA terlebih dahulu.');
            $this->redirect('/admin/security');
        }

        if (!password_verify((string) $this->input('password'), (string) $user['password_hash'])) {
            \flash('error', 'Kata sandi salah.');
            $this->redirect('/admin/security');
        }

        $codes = Totp::generateRecoveryCodes();
        User::setTotpRecovery((int) $user['id'], Totp::hashRecoveryCodes($codes));
        $_SESSION['_totp_recovery_plain'] = $codes;

        ActivityLog::record('2fa_recovery_regenerated', 'Membuat ulang kode pemulihan', (int) $user['id'], (string) $user['email']);
        \flash('success', 'Kode pemulihan baru dibuat. Kode lama tidak berlaku lagi.');
        $this->redirect('/admin/security');
    }

    /**
     * @return array<string, mixed>
     */
    private function currentUser(): array
    {
        $session = Auth::user();
        $user = $session !== null ? User::find((int) $session['id']) : null;

        if ($user === null) {
            \flash('error', 'Akun tidak ditemukan.');
            $this->redirect('/login');
        }

        return $user;
    }
}
