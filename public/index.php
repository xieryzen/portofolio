<?php

declare(strict_types=1);

use App\Router;
use App\View;

require dirname(__DIR__) . '/app/bootstrap.php';

// ---- Header keamanan ---------------------------------------------------
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');
header('Permissions-Policy: geolocation=(), microphone=(), camera=()');
header(
    "Content-Security-Policy: default-src 'self'; img-src 'self' data: blob:; "
    . "style-src 'self' 'unsafe-inline'; script-src 'self'; font-src 'self' data:; "
    . "form-action 'self'; base-uri 'self'"
);
if (is_secure()) {
    header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
}

// ---- Proteksi CSRF untuk semua request POST ----------------------------
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $token = $_POST['_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);

    if (!\csrf_valid(is_string($token) ? $token : null)) {
        http_response_code(419);
        header('Content-Type: text/html; charset=UTF-8');
        View::render('errors/419', ['title' => 'Sesi kedaluwarsa'], 'layouts/app');
        exit;
    }
}

$router = new Router();

// ---- Halaman publik ----------------------------------------------------
$router->get('/', [App\Controllers\HomeController::class, 'index']);
$router->get('/project/{slug}', [App\Controllers\HomeController::class, 'show']);
$router->get('/media/{file}', [App\Controllers\MediaController::class, 'show']);
$router->get('/download/{id}', [App\Controllers\DownloadController::class, 'file']);
$router->post('/contact', [App\Controllers\ContactController::class, 'store']);
$router->get('/manifest.webmanifest', [App\Controllers\PwaController::class, 'manifest']);
$router->get('/offline', [App\Controllers\PwaController::class, 'offline']);
$router->get('/sitemap.xml', [App\Controllers\SitemapController::class, 'index']);
$router->get('/robots.txt', [App\Controllers\SitemapController::class, 'robots']);

// ---- Autentikasi -------------------------------------------------------
$router->get('/login', [App\Controllers\AuthController::class, 'showLogin']);
$router->post('/login', [App\Controllers\AuthController::class, 'login']);
$router->get('/register', [App\Controllers\AuthController::class, 'showRegister']);
$router->post('/register', [App\Controllers\AuthController::class, 'register']);
$router->post('/logout', [App\Controllers\AuthController::class, 'logout']);

// ---- Area user ---------------------------------------------------------
$router->get('/dashboard', [App\Controllers\DashboardController::class, 'index']);
$router->get('/profil', [App\Controllers\DashboardController::class, 'profile']);
$router->post('/profil/password', [App\Controllers\DashboardController::class, 'updatePassword']);

// ---- Area admin --------------------------------------------------------
$router->get('/admin', [App\Controllers\Admin\DashboardController::class, 'index']);
$router->get('/admin/profile', [App\Controllers\Admin\ProfileController::class, 'edit']);
$router->post('/admin/profile', [App\Controllers\Admin\ProfileController::class, 'update']);
$router->post('/admin/profile/image', [App\Controllers\Admin\ProfileController::class, 'uploadImage']);
$router->post('/admin/profile/image/remove', [App\Controllers\Admin\ProfileController::class, 'removeImage']);

$router->get('/admin/projects', [App\Controllers\Admin\ProjectController::class, 'index']);
$router->get('/admin/projects/create', [App\Controllers\Admin\ProjectController::class, 'create']);
$router->post('/admin/projects', [App\Controllers\Admin\ProjectController::class, 'store']);
$router->get('/admin/projects/{id}/edit', [App\Controllers\Admin\ProjectController::class, 'edit']);
$router->post('/admin/projects/{id}', [App\Controllers\Admin\ProjectController::class, 'update']);
$router->post('/admin/projects/{id}/delete', [App\Controllers\Admin\ProjectController::class, 'destroy']);
$router->post('/admin/projects/{id}/status', [App\Controllers\Admin\ProjectController::class, 'toggleStatus']);
$router->post('/admin/projects/{id}/cover', [App\Controllers\Admin\ProjectController::class, 'uploadCover']);
$router->post('/admin/projects/{id}/cover/remove', [App\Controllers\Admin\ProjectController::class, 'removeCover']);

$router->post('/admin/projects/{id}/releases', [App\Controllers\Admin\ReleaseController::class, 'store']);
$router->post('/admin/releases/{id}', [App\Controllers\Admin\ReleaseController::class, 'update']);
$router->post('/admin/releases/{id}/status', [App\Controllers\Admin\ReleaseController::class, 'toggleStatus']);
$router->post('/admin/releases/{id}/file/remove', [App\Controllers\Admin\ReleaseController::class, 'removeFile']);
$router->post('/admin/releases/{id}/delete', [App\Controllers\Admin\ReleaseController::class, 'destroy']);

$router->post('/admin/projects/{id}/gallery', [App\Controllers\Admin\GalleryController::class, 'store']);
$router->post('/admin/gallery/{id}/caption', [App\Controllers\Admin\GalleryController::class, 'updateCaption']);
$router->post('/admin/gallery/{id}/delete', [App\Controllers\Admin\GalleryController::class, 'destroy']);

$router->get('/admin/skills', [App\Controllers\Admin\SkillController::class, 'index']);
$router->post('/admin/skills', [App\Controllers\Admin\SkillController::class, 'store']);
$router->post('/admin/skills/{id}', [App\Controllers\Admin\SkillController::class, 'update']);
$router->post('/admin/skills/{id}/delete', [App\Controllers\Admin\SkillController::class, 'destroy']);

$router->get('/admin/users', [App\Controllers\Admin\UserController::class, 'index']);
$router->post('/admin/users/{id}/role', [App\Controllers\Admin\UserController::class, 'updateRole']);
$router->post('/admin/users/{id}/status', [App\Controllers\Admin\UserController::class, 'toggleStatus']);
$router->post('/admin/users/{id}/delete', [App\Controllers\Admin\UserController::class, 'destroy']);

$router->get('/admin/messages', [App\Controllers\Admin\MessageController::class, 'index']);
$router->post('/admin/messages/{id}/read', [App\Controllers\Admin\MessageController::class, 'toggleRead']);
$router->post('/admin/messages/{id}/delete', [App\Controllers\Admin\MessageController::class, 'destroy']);

$router->get('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'edit']);
$router->post('/admin/settings', [App\Controllers\Admin\SettingsController::class, 'update']);
$router->post('/admin/settings/password', [App\Controllers\Admin\SettingsController::class, 'updatePassword']);

// ---- Cadangan (backup/restore) -----------------------------------------
$router->get('/admin/backup', [App\Controllers\Admin\BackupController::class, 'index']);
$router->get('/admin/backup/database', [App\Controllers\Admin\BackupController::class, 'database']);
$router->get('/admin/backup/archive', [App\Controllers\Admin\BackupController::class, 'archive']);
$router->get('/admin/backup/file/{name}', [App\Controllers\Admin\BackupController::class, 'downloadBackup']);
$router->post('/admin/backup/restore', [App\Controllers\Admin\BackupController::class, 'restore']);

$router->dispatch($_SERVER['REQUEST_METHOD'] ?? 'GET', $_SERVER['REQUEST_URI'] ?? '/');
