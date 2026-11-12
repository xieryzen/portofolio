<?php
/** @var string $content */
/** @var array<string, mixed> $profile */
/** @var string $accent */
/** @var string $siteTitle */

use App\View;

$pageTitle = isset($title) && $title !== '' ? (string) $title : $siteTitle;
$fullTitle = $pageTitle === $siteTitle ? $siteTitle : $pageTitle . ' — ' . $siteTitle;
$description = isset($metaDescription) && $metaDescription !== ''
    ? (string) $metaDescription
    : (string) ($profile['tagline'] ?? setting('meta_description', ''));
$initial = mb_strtoupper(mb_substr((string) ($profile['full_name'] ?? 'P'), 0, 1, 'UTF-8'), 'UTF-8');
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
    <meta name="description" content="<?= e(excerpt($description, 180)) ?>">
    <meta name="theme-color" content="#060a14">
    <meta name="color-scheme" content="dark">
    <link rel="icon" href="<?= e($favicon) ?>">
    <?php
    $canonical = absolute_url((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    $ogSource = $profile['cover'] ?? $profile['avatar'] ?? null;
    $ogImage = $ogSource ? absolute_url((string) media_url((string) $ogSource)) : null;
    ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <meta property="og:type" content="website">
    <meta property="og:title" content="<?= e($fullTitle) ?>">
    <meta property="og:description" content="<?= e(excerpt($description, 180)) ?>">
    <meta property="og:url" content="<?= e($canonical) ?>">
    <meta name="twitter:card" content="<?= $ogImage ? 'summary_large_image' : 'summary' ?>">
    <?php if ($ogImage): ?>
        <meta property="og:image" content="<?= e($ogImage) ?>">
        <meta name="twitter:image" content="<?= e($ogImage) ?>">
    <?php endif; ?>
    <script src="<?= e(asset('assets/js/theme.js')) ?>"></script>
    <link rel="stylesheet" href="<?= e(asset('assets/css/app.css')) ?>">
</head>
<body>
<a class="skip-link" href="#main">Langsung ke konten</a>
<?= View::partial('partials/navbar') ?>
<?= View::partial('partials/flash') ?>

<main id="main">
    <?= $content ?>
</main>

<?= View::partial('partials/footer') ?>
<?= View::partial('partials/lightbox') ?>
<script src="<?= e(asset('assets/js/app.js')) ?>" defer></script>
</body>
</html>
