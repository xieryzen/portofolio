<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Controllers\Controller;
use App\Models\User;
use App\Notifier;
use App\Settings;
use App\Validator;

final class SettingsController extends Controller
{
    public function edit(): void
    {
        Auth::requireOwner();

        $this->view('admin/settings', [
            'title' => 'Pengaturan',
            'user' => Auth::user(),
            'notifyChannels' => Notifier::channels(),
            'notifyEnabled' => Notifier::enabled(),
            'notifyLogs' => Notifier::recentLogs(),
        ], 'layouts/admin');
    }

    public function update(): void
    {
        Auth::requireOwner();

        $data = [
            'site_title' => $this->inputString('site_title'),
            'meta_description' => $this->inputString('meta_description'),
            'accent_color' => $this->inputString('accent_color'),
            'footer_text' => $this->inputString('footer_text'),
            'show_contact' => $this->input('show_contact') === '1' ? '1' : '0',
            'allow_registration' => $this->input('allow_registration') === '1' ? '1' : '0',
            'notify_contact' => $this->input('notify_contact') === '1' ? '1' : '0',
        ];

        $validator = new Validator(
            $data,
            [
                'site_title' => 'required|min:2|max:80',
                'meta_description' => 'max:300',
                'footer_text' => 'max:200',
            ],
            ['site_title' => 'Judul situs', 'meta_description' => 'Deskripsi meta']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), $data, '/admin/settings');
        }

        if (!preg_match('~^#[0-9a-fA-F]{6}$~', $data['accent_color'])) {
            $data['accent_color'] = '#6d8bff';
        }

        Settings::setMany($data);
        Settings::flush();

        \flash('success', 'Pengaturan situs disimpan.');
        $this->redirect('/admin/settings');
    }

    public function testNotify(): void
    {
        Auth::requireOwner();

        $results = Notifier::sendTest();

        if ($results === []) {
            \flash('info', 'Belum ada kanal notifikasi yang dikonfigurasi. Isi TELEGRAM_BOT_TOKEN + TELEGRAM_CHAT_ID, NOTIFY_WEBHOOK_URL, atau SMTP_* di .env lalu restart server.');
            $this->redirect('/admin/settings');
        }

        $ok = array_values(array_filter($results, static fn (array $item): bool => $item['ok'] === true));
        $failed = array_values(array_filter($results, static fn (array $item): bool => $item['ok'] === false));

        $lines = [];
        foreach ($results as $item) {
            $lines[] = $item['channel'] . ': ' . ($item['ok'] ? 'berhasil' : 'gagal — ' . $item['detail']);
        }

        if ($failed === []) {
            \flash('success', 'Tes notifikasi terkirim — ' . implode('; ', $lines));
        } elseif ($ok === []) {
            \flash('error', 'Semua kanal gagal — ' . implode('; ', $lines));
        } else {
            \flash('info', implode('; ', $lines));
        }

        $this->redirect('/admin/settings');
    }

    public function updatePassword(): void
    {
        Auth::requireOwner();

        $user = Auth::user();
        if (!$user) {
            $this->redirect('/login');
        }

        $name = $this->inputString('name');
        $email = mb_strtolower($this->inputString('email'));
        $current = (string) $this->input('current_password');
        $password = (string) $this->input('password');

        $validator = new Validator(
            ['name' => $name, 'email' => $email],
            [
                'name' => 'required|min:2|max:120',
                'email' => 'required|email|max:190|unique:users,email,' . (int) $user['id'],
            ],
            ['name' => 'Nama', 'email' => 'Email']
        );

        if ($validator->fails()) {
            $this->backWithErrors($validator->errors(), ['name' => $name, 'email' => $email], '/admin/settings');
        }

        if (!password_verify($current, (string) $user['password_hash'])) {
            $this->backWithErrors(['current_password' => 'Kata sandi saat ini salah.'], [], '/admin/settings');
        }

        if ($password !== '') {
            if (mb_strlen($password) < 8) {
                $this->backWithErrors(['password' => 'Kata sandi baru minimal 8 karakter.'], [], '/admin/settings');
            }
            User::updatePassword((int) $user['id'], $password);
        }

        User::updateProfile((int) $user['id'], $name, $email);
        $this->clearFormState();
        \flash('success', 'Data akun berhasil diperbarui.');
        $this->redirect('/admin/settings');
    }
}
