<?php
/** @var array<string, mixed> $profile */
/** @var array<string, mixed>|null $authUser */

$name = (string) ($profile['full_name'] ?? 'Portfolio');
$headline = (string) ($profile['headline'] ?? '');
$brand = setting('site_title', 'Portfolio');
$avatar = media_url($profile['avatar'] ?? null);
$isHome = current_path() === '/';
$cvUrl = setting('cv_file', '') !== '' ? app_url('/cv') : null;
?>
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a class="brand" href="<?= e(app_url('/')) ?>" aria-label="<?= e(t('nav.home')) ?>">
                <span class="brand-mark">
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="<?= e($name) ?>">
                    <?php else: ?>
                        <?= e(initials($name)) ?>
                    <?php endif; ?>
                </span>
                <span class="brand-name">
                    <strong><?= e($brand) ?></strong>
                    <span><?= e($headline !== '' ? $headline : t('hero.portfolio')) ?></span>
                </span>
            </a>

            <nav class="site-nav" aria-label="<?= e(t('nav.main')) ?>">
                <a href="<?= e(app_url('/')) ?>" class="<?= $isHome ? 'is-active' : '' ?>"><?= e(t('nav.home')) ?></a>
                <a href="<?= e(app_url('/#karya')) ?>"><?= e(t('nav.work')) ?></a>
                <a href="<?= e(app_url('/blog')) ?>" class="<?= str_starts_with(current_path(), '/blog') ? 'is-active' : '' ?>"><?= e(t('nav.blog')) ?></a>
                <a href="<?= e(app_url('/#tentang')) ?>"><?= e(t('nav.about')) ?></a>
                <?php if (!empty($hasGallery)): ?>
                    <a href="<?= e(app_url('/#dokumentasi')) ?>"><?= e(t('nav.gallery')) ?></a>
                <?php endif; ?>
                <a href="<?= e(app_url('/#kontak')) ?>"><?= e(t('nav.contact')) ?></a>
            </nav>

            <div class="header-actions">
                <?php if ($cvUrl !== null): ?>
                    <a class="btn btn-ghost btn-sm desktop-only" href="<?= e($cvUrl) ?>" rel="nofollow" download>
                        <?= svg_icon('download', 'icon-sm') ?> <?= e(t('hero.download_cv')) ?>
                    </a>
                <?php endif; ?>

                <?php if ($authUser): ?>
                    <a class="btn btn-ghost btn-sm desktop-only" href="<?= e(app_url($isAdmin ? '/admin' : '/dashboard')) ?>">
                        <?= svg_icon($isAdmin ? 'dashboard' : 'user', 'icon-sm') ?>
                        <?= e($isAdmin ? t('nav.admin') : t('nav.dashboard')) ?>
                    </a>
                    <form method="post" action="<?= e(app_url('/logout')) ?>" class="desktop-only">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-sm" type="submit"><?= svg_icon('logout', 'icon-sm') ?> <?= e(t('nav.logout')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm desktop-only" href="<?= e(app_url('/login')) ?>"><?= e(t('nav.login')) ?></a>
                    <?php if ($registrationOpen): ?>
                        <a class="btn btn-primary btn-sm desktop-only" href="<?= e(app_url('/register')) ?>"><?= e(t('nav.register')) ?></a>
                    <?php endif; ?>
                <?php endif; ?>

                <div class="lang-switch" role="group" aria-label="<?= e(t('nav.language')) ?>">
                    <a href="<?= e(\App\Lang::switchUrl('id')) ?>" class="<?= locale_is('id') ? 'is-active' : '' ?>" hreflang="id" lang="id">ID</a>
                    <a href="<?= e(\App\Lang::switchUrl('en')) ?>" class="<?= locale_is('en') ? 'is-active' : '' ?>" hreflang="en" lang="en">EN</a>
                </div>

                <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="<?= e(t('nav.theme')) ?>" title="<?= e(t('nav.theme')) ?>">
                    <span class="theme-icon theme-icon-moon"><?= svg_icon('moon') ?></span>
                    <span class="theme-icon theme-icon-sun"><?= svg_icon('sun') ?></span>
                </button>

                <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="<?= e(t('nav.menu')) ?>">
                    <?= svg_icon('menu') ?>
                </button>
            </div>
        </div>
    </div>

    <div class="mobile-nav" id="mobile-nav" data-mobile-nav>
        <div class="container">
            <a href="<?= e(app_url('/')) ?>"><?= e(t('nav.home')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <a href="<?= e(app_url('/#karya')) ?>"><?= e(t('nav.work')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <a href="<?= e(app_url('/blog')) ?>"><?= e(t('nav.blog')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <a href="<?= e(app_url('/#tentang')) ?>"><?= e(t('nav.about')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <?php if (!empty($hasGallery)): ?>
                <a href="<?= e(app_url('/#dokumentasi')) ?>"><?= e(t('nav.gallery')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <?php endif; ?>
            <a href="<?= e(app_url('/#kontak')) ?>"><?= e(t('nav.contact')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <?php if ($cvUrl !== null): ?>
                <a href="<?= e($cvUrl) ?>" rel="nofollow" download><?= e(t('hero.download_cv')) ?> <?= svg_icon('download', 'icon-sm') ?></a>
            <?php endif; ?>

            <div class="lang-switch" role="group" aria-label="<?= e(t('nav.language')) ?>">
                <a href="<?= e(\App\Lang::switchUrl('id')) ?>" class="<?= locale_is('id') ? 'is-active' : '' ?>" hreflang="id" lang="id">ID</a>
                <a href="<?= e(\App\Lang::switchUrl('en')) ?>" class="<?= locale_is('en') ? 'is-active' : '' ?>" hreflang="en" lang="en">EN</a>
            </div>

            <div class="mobile-actions">
                <?php if ($authUser): ?>
                    <a class="btn btn-soft" href="<?= e(app_url($isAdmin ? '/admin' : '/dashboard')) ?>">
                        <?= e($isAdmin ? t('nav.admin') : t('nav.dashboard')) ?>
                    </a>
                    <form method="post" action="<?= e(app_url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-block" type="submit"><?= e(t('nav.logout')) ?></button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('/login')) ?>"><?= e(t('nav.login')) ?></a>
                    <?php if ($registrationOpen): ?>
                        <a class="btn btn-primary" href="<?= e(app_url('/register')) ?>"><?= e(t('nav.register')) ?></a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
