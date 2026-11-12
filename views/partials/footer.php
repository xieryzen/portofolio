<?php
/** @var array<string, mixed> $profile */

$socials = [
    'github' => 'GitHub',
    'linkedin' => 'LinkedIn',
    'instagram' => 'Instagram',
    'x' => 'X',
    'website' => 'Website',
];
$links = array_filter(
    array_map(
        static fn (string $key): ?array => !empty($profile[$key]) ? ['key' => $key, 'url' => social_url($key, (string) $profile[$key])] : null,
        array_keys($socials)
    )
);
?>
<footer class="site-footer">
    <div class="container">
        <div class="footer-inner">
            <div class="flex" style="gap:14px">
                <span class="brand-mark" style="width:34px;height:34px;border-radius:10px;font-size:13px">
                    <?= e(initials((string) ($profile['full_name'] ?? 'P'))) ?>
                </span>
                <div>
                    <p style="color:var(--text);font-weight:600;font-size:13.5px"><?= e((string) ($profile['full_name'] ?? '')) ?></p>
                    <p><?= e(setting('footer_text', 'Dibuat dengan PHP 8.3.')) ?></p>
                </div>
            </div>

            <div class="footer-links">
                <?php foreach ($links as $link): ?>
                    <a href="<?= e($link['url']) ?>" target="_blank" rel="noopener noreferrer nofollow"><?= e($socials[$link['key']]) ?></a>
                <?php endforeach; ?>
                <a href="<?= e(app_url('/#kontak')) ?>"><?= e(t('nav.contact')) ?></a>
                <a href="<?= e(\App\Lang::switchUrl(locale_is('id') ? 'en' : 'id')) ?>" hreflang="<?= locale_is('id') ? 'en' : 'id' ?>">
                    <?= locale_is('id') ? 'English' : 'Indonesia' ?>
                </a>
                <span>© <?= date('Y') ?></span>
            </div>
        </div>
    </div>
</footer>
