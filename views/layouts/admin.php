<?php
/** @var string $content */
/** @var array<string, mixed> $profile */
/** @var array<string, mixed>|null $authUser */
/** @var string $accent */
/** @var string $siteTitle */

use App\Models\Message;
use App\View;

$pageTitle = isset($title) && $title !== '' ? (string) $title : 'Panel Admin';
$fullTitle = $pageTitle . ' — ' . $siteTitle;
$initial = mb_strtoupper(mb_substr((string) ($profile['full_name'] ?? 'P'), 0, 1, 'UTF-8'), 'UTF-8');
$favicon = 'data:image/svg+xml,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="16" fill="' . $accent . '"/>'
    . '<text x="32" y="44" font-family="Arial,Helvetica,sans-serif" font-size="34" font-weight="700" fill="#08101f" text-anchor="middle">'
    . e($initial) . '</text></svg>'
);

$unread = 0;
try {
    $unread = Message::unreadCount();
} catch (\Throwable) {
    $unread = 0;
}

$menu = [
    ['label' => 'Ringkasan', 'path' => '/admin', 'icon' => 'dashboard', 'exact' => true],
    ['label' => 'Portofolio', 'path' => '/admin/projects', 'icon' => 'folder'],
    ['label' => 'Profil', 'path' => '/admin/profile', 'icon' => 'user'],
    ['label' => 'Keahlian', 'path' => '/admin/skills', 'icon' => 'sparkles'],
    ['label' => 'Pengguna', 'path' => '/admin/users', 'icon' => 'users'],
    ['label' => 'Pesan', 'path' => '/admin/messages', 'icon' => 'message', 'badge' => $unread],
    ['label' => 'Pengaturan', 'path' => '/admin/settings', 'icon' => 'settings'],
];
?>
<!doctype html>
<html lang="id" style="--accent: <?= e($accent) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="robots" content="noindex,nofollow">
    <meta name="theme-color" content="#060a14">
    <meta name="color-scheme" content="dark">
    <link rel="icon" href="<?= e($favicon) ?>">
    <script src="<?= e(asset('assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body class="admin-body">
<?= View::partial('partials/flash') ?>

<div class="admin-shell">
    <aside class="admin-sidebar" data-sidebar>
        <a class="brand" href="<?= e(app_url('/admin')) ?>">
            <span class="brand-mark"><?= e(initials((string) ($profile['full_name'] ?? 'P'))) ?></span>
            <span class="brand-name">
                <strong>Panel Admin</strong>
                <span><?= e($siteTitle) ?></span>
            </span>
        </a>

        <div>
            <p class="side-label">Konten</p>
            <nav class="side-nav" aria-label="Navigasi admin">
                <?php foreach (array_slice($menu, 0, 4) as $item): ?>
                    <a href="<?= e(app_url($item['path'])) ?>" class="<?= is_active_path($item['path'], $item['exact'] ?? false) ? 'is-active' : '' ?>">
                        <?= svg_icon($item['icon']) ?> <?= e($item['label']) ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div>
            <p class="side-label">Interaksi</p>
            <nav class="side-nav" aria-label="Navigasi interaksi">
                <?php foreach (array_slice($menu, 4, 2) as $item): ?>
                    <a href="<?= e(app_url($item['path'])) ?>" class="<?= is_active_path($item['path']) ? 'is-active' : '' ?>">
                        <?= svg_icon($item['icon']) ?> <?= e($item['label']) ?>
                        <?php if (!empty($item['badge'])): ?><span class="badge"><?= (int) $item['badge'] ?></span><?php endif; ?>
                    </a>
                <?php endforeach; ?>
            </nav>
        </div>

        <div>
            <p class="side-label">Sistem</p>
            <nav class="side-nav" aria-label="Navigasi sistem">
                <a href="<?= e(app_url('/admin/security')) ?>" class="<?= is_active_path('/admin/security') ? 'is-active' : '' ?>">
                    <?= svg_icon('lock') ?> Keamanan
                </a>
                <?php if (($authUser['role'] ?? '') === 'owner'): ?>
                    <a href="<?= e(app_url('/admin/activity')) ?>" class="<?= is_active_path('/admin/activity') ? 'is-active' : '' ?>">
                        <?= svg_icon('clock') ?> Aktivitas
                    </a>
                <?php endif; ?>
                <a href="<?= e(app_url('/admin/backup')) ?>" class="<?= is_active_path('/admin/backup') ? 'is-active' : '' ?>">
                    <?= svg_icon('database') ?> Cadangan
                </a>
                <a href="<?= e(app_url('/admin/settings')) ?>" class="<?= is_active_path('/admin/settings') ? 'is-active' : '' ?>">
                    <?= svg_icon('settings') ?> Pengaturan
                </a>
                <a href="<?= e(app_url('/')) ?>"><?= svg_icon('external') ?> Lihat situs</a>
            </nav>
        </div>

        <div class="side-foot">
            <div class="admin-user">
                <span class="avatar"><?= e(initials((string) ($authUser['name'] ?? 'A'))) ?></span>
                <div>
                    <strong><?= e((string) ($authUser['name'] ?? 'Admin')) ?></strong>
                    <span><?= e((string) ($authUser['role'] ?? 'admin')) ?></span>
                </div>
            </div>
            <form method="post" action="<?= e(app_url('/logout')) ?>">
                <?= csrf_field() ?>
                <button class="btn btn-ghost btn-sm btn-block" type="submit"><?= svg_icon('logout', 'icon-sm') ?> Keluar</button>
            </form>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <button class="icon-btn sidebar-toggle" type="button" data-sidebar-toggle aria-label="Buka menu">
                <?= svg_icon('menu') ?>
            </button>
            <span class="page-title"><?= e($pageTitle) ?></span>
            <div class="topbar-actions">
                <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Ganti tema" title="Ganti tema">
                    <span class="theme-icon theme-icon-moon"><?= svg_icon('moon') ?></span>
                    <span class="theme-icon theme-icon-sun"><?= svg_icon('sun') ?></span>
                </button>
                <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/')) ?>" target="_blank" rel="noopener">
                    <?= svg_icon('external', 'icon-sm') ?> Lihat situs
                </a>
                <?php if ($unread > 0): ?>
                    <a class="icon-btn" href="<?= e(app_url('/admin/messages')) ?>" title="<?= $unread ?> pesan belum dibaca" style="position:relative">
                        <?= svg_icon('message') ?>
                    </a>
                <?php endif; ?>
            </div>
        </header>

        <div class="admin-content">
            <?= $content ?>
        </div>
    </div>
</div>

<div class="modal" data-confirm-modal role="dialog" aria-modal="true" aria-labelledby="confirm-title">
    <div class="modal-card">
        <h3 id="confirm-title" data-confirm-title>Konfirmasi</h3>
        <p data-confirm-text>Apakah Anda yakin?</p>
        <div class="modal-actions">
            <button class="btn btn-ghost btn-sm" type="button" data-confirm-cancel>Batal</button>
            <button class="btn btn-danger btn-sm" type="button" data-confirm-ok>Ya, lanjutkan</button>
        </div>
    </div>
</div>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
