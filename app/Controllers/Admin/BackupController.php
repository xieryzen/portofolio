<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\Backup;
use App\Controllers\Controller;
use App\Models\ActivityLog;

final class BackupController extends Controller
{
    public function index(): void
    {
        Auth::requireOwner();

        $this->view('admin/backup', [
            'title' => 'Cadangan data',
            'databaseSize' => Backup::databaseSize(),
            'databasePath' => Backup::databasePath(),
            'driver' => Backup::driver(),
            'isSqlite' => Backup::isSqlite(),
            'storage' => Backup::storageStats(),
            'maxMb' => Backup::maxMb(),
            'backups' => Backup::listBackups(),
        ], 'layouts/admin');
    }

    public function database(): void
    {
        Auth::requireOwner();

        if (!Backup::isSqlite()) {
            \flash('error', 'Unduh database langsung hanya tersedia untuk SQLite. Gunakan mysqldump untuk MySQL.');
            $this->redirect('/admin/backup');
        }

        $path = Backup::databasePath();

        if (!is_file($path)) {
            \flash('error', 'Berkas database belum ada.');
            $this->redirect('/admin/backup');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="portfolio-database-' . date('Ymd-His') . '.sqlite"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($path);
        exit;
    }

    public function archive(): void
    {
        Auth::requireOwner();

        $result = Backup::createArchive();

        if (!$result['ok'] || !isset($result['path'])) {
            \flash('error', $result['error'] ?? 'Gagal membuat cadangan.');
            $this->redirect('/admin/backup');
        }

        $path = (string) $result['path'];

        header('Content-Type: application/zip');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="portfolio-backup-' . date('Ymd-His') . '.zip"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($path);
        @unlink($path);
        exit;
    }

    public function downloadBackup(string $name): void
    {
        Auth::requireOwner();

        $name = basename(rawurldecode($name));

        if (preg_match('~^[A-Za-z0-9][A-Za-z0-9._-]*\.sqlite$~', $name) !== 1) {
            \flash('error', 'Nama berkas cadangan tidak valid.');
            $this->redirect('/admin/backup');
        }

        $path = Backup::backupsDir() . '/' . $name;

        if (!is_file($path)) {
            \flash('error', 'Berkas cadangan tidak ditemukan.');
            $this->redirect('/admin/backup');
        }

        header('Content-Type: application/octet-stream');
        header('Content-Length: ' . (string) filesize($path));
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: private, no-store');

        readfile($path);
        exit;
    }

    public function restore(): void
    {
        Auth::requireOwner();

        if ($this->input('confirm') !== '1') {
            \flash('error', 'Centang konfirmasi sebelum memulihkan data.');
            $this->redirect('/admin/backup');
        }

        $file = $_FILES['backup'] ?? null;

        if (!is_array($file) || (int) ($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            \flash('error', 'Pilih berkas ZIP cadangan terlebih dahulu.');
            $this->redirect('/admin/backup');
        }

        if ((int) $file['error'] !== UPLOAD_ERR_OK) {
            \flash('error', 'Upload gagal (kode ' . (int) $file['error'] . '). Pastikan ukuran berkas di bawah ' . Backup::maxMb() . ' MB.');
            $this->redirect('/admin/backup');
        }

        $maxBytes = Backup::maxMb() * 1024 * 1024;
        if ((int) ($file['size'] ?? 0) > $maxBytes) {
            \flash('error', 'Ukuran ZIP maksimal ' . Backup::maxMb() . ' MB.');
            $this->redirect('/admin/backup');
        }

        $extension = strtolower((string) pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));
        if ($extension !== 'zip') {
            \flash('error', 'Berkas cadangan harus berupa ZIP.');
            $this->redirect('/admin/backup');
        }

        $temporary = (string) ($file['tmp_name'] ?? '');
        if ($temporary === '' || !is_file($temporary)) {
            \flash('error', 'Berkas upload tidak ditemukan.');
            $this->redirect('/admin/backup');
        }

        $result = Backup::restore($temporary);

        if (!$result['ok']) {
            \flash('error', $result['error'] ?? 'Restore gagal.');
            $this->redirect('/admin/backup');
        }

        ActivityLog::record('backup_restored', 'Memulihkan data dari berkas cadangan');
        \flash('success', $result['message'] ?? 'Data berhasil dipulihkan.');
        $this->redirect('/admin/backup');
    }
}
