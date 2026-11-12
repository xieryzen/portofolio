<?php
/** @var string $content */
/** @var array<string, mixed> $profile */
/** @var string $accent */
/** @var string $siteTitle */

use App\View;

$pageTitle = isset($title) && $title !== '' ? (string) $title : 'Masuk';
$fullTitle = $pageTitle . ' — ' . $siteTitle;
$name = (string) ($profile['full_name'] ?? $siteTitle);
$initial = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
$favicon = 'data:image/svg+xml,' . rawurlencode(
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64"><rect width="64" height="64" rx="16" fill="' . $accent . '"/>'
    . '<text x="32" y="44" font-family="Arial,Helvetica,sans-serif" font-size="34" font-weight="700" fill="#08101f" text-anchor="middle">'
    . e($initial) . '</text></svg>'
);
?>
<!doctype html>
<html lang="id" style="--accent: <?= e($accent) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e(excerpt((string) ($profile['tagline'] ?? ''), 160)) ?>">
    <meta name="theme-color" content="#060a14">
    <meta name="color-scheme" content="dark">
    <link rel="icon" href="<?= e($favicon) ?>">
    <script src="<?= e(asset('assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<?= View::partial('partials/flash') ?>

<div class="auth-shell">
    <aside class="auth-aside">
        <a class="brand" href="<?= e(app_url('/')) ?>">
            <span class="brand-mark"><?= e(initials($name)) ?></span>
            <span class="brand-name">
                <strong><?= e($siteTitle) ?></strong>
                <span><?= e((string) ($profile['headline'] ?? 'Portofolio')) ?></span>
            </span>
        </a>

        <div class="auth-quote">
            <div class="quote-mark">“</div>
            <blockquote><?= e((string) ($profile['tagline'] ?: 'Setiap karya punya cerita. Masuk untuk melihat portofolio lengkapnya.')) ?></blockquote>
            <p><?= e($name) ?><?php if (!empty($profile['location'])): ?> · <?= e((string) $profile['location']) ?><?php endif; ?></p>

            <ul class="auth-features">
                <li><?= svg_icon('check') ?> Dokumentasi visual setiap project</li>
                <li><?= svg_icon('check') ?> Profil & keahlian yang selalu diperbarui</li>
                <li><?= svg_icon('check') ?> Kontak langsung tanpa perantara</li>
            </ul>
        </div>

        <p class="small muted">© <?= date('Y') ?> <?= e($siteTitle) ?> — dibuat dengan PHP 8.3</p>
    </aside>

    <div class="auth-main">
        <div class="auth-card">
            <a class="brand" href="<?= e(app_url('/')) ?>">
                <span class="brand-mark"><?= e(initials($name)) ?></span>
                <span class="brand-name">
                    <strong><?= e($siteTitle) ?></strong>
                    <span>Portofolio</span>
                </span>
            </a>
            <?= $content ?>
        </div>
    </div>
</div>

<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
