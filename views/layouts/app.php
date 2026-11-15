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
$customFavicon = brand_favicon();
$favicon = $customFavicon ?: asset('icons/favicon-32.png');
?>
<!doctype html>
<html lang="<?= e(\App\Lang::htmlLang()) ?>" style="--accent: <?= e($accent) ?>" data-sw="<?= e(app_url('/sw.js')) ?>">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($fullTitle) ?></title>
    <meta name="description" content="<?= e(excerpt($description, 180)) ?>">
    <meta name="theme-color" content="#060a14">
    <meta name="color-scheme" content="dark">
    <link rel="icon" href="<?= e($favicon) ?>">
    <link rel="apple-touch-icon" href="<?= e(brand_favicon() ?: asset('icons/apple-touch-icon.png')) ?>">
    <link rel="manifest" href="<?= e(asset('manifest.webmanifest')) ?>">
    <link rel="alternate" type="application/rss+xml" title="<?= e($siteTitle) ?> — RSS" href="<?= e(absolute_url('/feed.xml')) ?>">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-title" content="<?= e($siteTitle) ?>">
    <?php
    $canonical = absolute_url((string) ($_SERVER['REQUEST_URI'] ?? '/'));
    $ogSource = $profile['cover'] ?? $profile['avatar'] ?? null;
    $ogImage = $ogSource
        ? absolute_url((string) media_url((string) $ogSource))
        : absolute_url(asset('assets/img/og-cover.jpg'));
    ?>
    <link rel="canonical" href="<?= e($canonical) ?>">
    <link rel="alternate" hreflang="id" href="<?= e(absolute_url(\App\Lang::switchUrl('id'))) ?>">
    <link rel="alternate" hreflang="en" href="<?= e(absolute_url(\App\Lang::switchUrl('en'))) ?>">
    <link rel="alternate" hreflang="x-default" href="<?= e(absolute_url(\App\Lang::switchUrl('id'))) ?>">
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
<a class="skip-link" href="#main"><?= e(t('nav.skip')) ?></a>
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
