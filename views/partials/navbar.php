<?php
/** @var array<string, mixed> $profile */
/** @var array<string, mixed>|null $authUser */

$name = (string) ($profile['full_name'] ?? 'Portfolio');
$headline = (string) ($profile['headline'] ?? '');
$brand = setting('site_title', 'Portfolio');
$avatar = media_url($profile['avatar'] ?? null);
$isHome = current_path() === '/';
?>
<header class="site-header">
    <div class="container">
        <div class="header-inner">
            <a class="brand" href="<?= e(app_url('/')) ?>" aria-label="Beranda">
                <span class="brand-mark">
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="<?= e($name) ?>">
                    <?php else: ?>
                        <?= e(initials($name)) ?>
                    <?php endif; ?>
                </span>
                <span class="brand-name">
                    <strong><?= e($brand) ?></strong>
                    <span><?= e($headline !== '' ? $headline : 'Portofolio') ?></span>
                </span>
            </a>

            <nav class="site-nav" aria-label="Navigasi utama">
                <a href="<?= e(app_url('/')) ?>" class="<?= $isHome ? 'is-active' : '' ?>">Beranda</a>
                <a href="<?= e(app_url('/#karya')) ?>">Karya</a>
                <a href="<?= e(app_url('/#tentang')) ?>">Tentang</a>
                <?php if (!empty($hasGallery)): ?>
                    <a href="<?= e(app_url('/#dokumentasi')) ?>">Dokumentasi</a>
                <?php endif; ?>
                <a href="<?= e(app_url('/#kontak')) ?>">Kontak</a>
            </nav>

            <div class="header-actions">
                <?php if ($authUser): ?>
                    <a class="btn btn-ghost btn-sm desktop-only" href="<?= e(app_url($isAdmin ? '/admin' : '/dashboard')) ?>">
                        <?= svg_icon($isAdmin ? 'dashboard' : 'user', 'icon-sm') ?>
                        <?= $isAdmin ? 'Panel Admin' : 'Dashboard' ?>
                    </a>
                    <form method="post" action="<?= e(app_url('/logout')) ?>" class="desktop-only">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-sm" type="submit"><?= svg_icon('logout', 'icon-sm') ?> Keluar</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost btn-sm desktop-only" href="<?= e(app_url('/login')) ?>">Masuk</a>
                    <?php if ($registrationOpen): ?>
                        <a class="btn btn-primary btn-sm desktop-only" href="<?= e(app_url('/register')) ?>">Daftar</a>
                    <?php endif; ?>
                <?php endif; ?>

                <button class="icon-btn theme-toggle" type="button" data-theme-toggle aria-label="Ganti tema" title="Ganti tema">
                    <span class="theme-icon theme-icon-moon"><?= svg_icon('moon') ?></span>
                    <span class="theme-icon theme-icon-sun"><?= svg_icon('sun') ?></span>
                </button>

                <button class="nav-toggle" type="button" data-nav-toggle aria-expanded="false" aria-controls="mobile-nav" aria-label="Buka menu">
                    <?= svg_icon('menu') ?>
                </button>
            </div>
        </div>
    </div>

    <div class="mobile-nav" id="mobile-nav" data-mobile-nav>
        <div class="container">
            <a href="<?= e(app_url('/')) ?>">Beranda <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <a href="<?= e(app_url('/#karya')) ?>">Karya <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <a href="<?= e(app_url('/#tentang')) ?>">Tentang <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <?php if (!empty($hasGallery)): ?>
                <a href="<?= e(app_url('/#dokumentasi')) ?>">Dokumentasi <?= svg_icon('arrow-right', 'icon-sm') ?></a>
            <?php endif; ?>
            <a href="<?= e(app_url('/#kontak')) ?>">Kontak <?= svg_icon('arrow-right', 'icon-sm') ?></a>

            <div class="mobile-actions">
                <?php if ($authUser): ?>
                    <a class="btn btn-soft" href="<?= e(app_url($isAdmin ? '/admin' : '/dashboard')) ?>">
                        <?= $isAdmin ? 'Panel Admin' : 'Dashboard' ?>
                    </a>
                    <form method="post" action="<?= e(app_url('/logout')) ?>">
                        <?= csrf_field() ?>
                        <button class="btn btn-ghost btn-block" type="submit">Keluar</button>
                    </form>
                <?php else: ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('/login')) ?>">Masuk</a>
                    <?php if ($registrationOpen): ?>
                        <a class="btn btn-primary" href="<?= e(app_url('/register')) ?>">Daftar</a>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</header>
