<?php
/** @var array<string, mixed> $post */
$cover = media_url($post['cover'] ?? null);
$url = app_url('/blog/' . $post['slug']);
$published = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
$date = $published !== '' ? format_date($published, 'd M Y') : '';
?>
<article class="project-card post-card" data-reveal>
    <a class="project-cover" href="<?= e($url) ?>" aria-label="<?= e((string) $post['title']) ?>">
        <?php if ($cover): ?>
            <img src="<?= e($cover) ?>" alt="<?= e((string) $post['title']) ?>" loading="lazy">
        <?php else: ?>
            <span class="cover-fallback"><?= e(initials((string) $post['title'])) ?></span>
        <?php endif; ?>
        <span class="project-badge"><?= e((string) ($post['category'] ?: t('blog.label'))) ?></span>
        <?php if ((int) ($post['is_featured'] ?? 0) === 1): ?>
            <span class="project-featured-flag" title="<?= e(t('blog.featured')) ?>"><?= svg_icon('star') ?></span>
        <?php endif; ?>
    </a>

    <div class="project-body">
        <h3><a href="<?= e($url) ?>"><?= e((string) $post['title']) ?></a></h3>
        <p><?= e(excerpt((string) ($post['excerpt'] ?: $post['content']), 130)) ?></p>

        <div class="project-meta">
            <?php if ($date !== ''): ?>
                <span><?= e($date) ?></span>
                <span class="dot-sep"></span>
            <?php endif; ?>
            <span><?= max(1, (int) ($post['reading_minutes'] ?? 1)) ?> <?= e(t('blog.read')) ?></span>
            <a class="project-link" href="<?= e($url) ?>"><?= e(t('card.detail')) ?> <?= svg_icon('arrow-up-right') ?></a>
        </div>
    </div>
</article>
