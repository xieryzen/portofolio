<?php
/** @var array<string, mixed> $project */
$cover = media_url($project['cover'] ?? null);
$title = localized($project, 'title');
$url = app_url('/project/' . $project['slug']);
$tech = array_filter(array_map('trim', explode(',', (string) ($project['tech_stack'] ?? ''))));
?>
<article class="project-card" data-reveal>
    <a class="project-cover" href="<?= e($url) ?>" aria-label="<?= e(t('card.view')) ?> <?= e((string) $project['title']) ?>">
        <?php if ($cover): ?>
            <img src="<?= e($cover) ?>" alt="Cover <?= e($title) ?>" loading="lazy">
        <?php else: ?>
            <span class="cover-fallback"><?= e(initials($title)) ?></span>
        <?php endif; ?>
        <span class="project-badge"><?= e((string) ($project['category'] ?: 'Project')) ?></span>
        <?php if ((int) ($project['is_featured'] ?? 0) === 1): ?>
            <span class="project-featured-flag" title="<?= e(t('card.featured')) ?>"><?= svg_icon('star') ?></span>
        <?php endif; ?>
    </a>

    <div class="project-body">
        <h3><a href="<?= e($url) ?>"><?= e($title) ?></a></h3>
        <p><?= e(excerpt(localized($project, 'summary') ?: localized($project, 'description'), 120)) ?></p>

        <div class="project-meta">
            <?php if (!empty($project['year'])): ?>
                <span><?= e((string) $project['year']) ?></span>
                <span class="dot-sep"></span>
            <?php endif; ?>
            <span><?= e($tech !== [] ? (string) reset($tech) : (string) ($project['category'] ?? '')) ?></span>
            <a class="project-link" href="<?= e($url) ?>"><?= e(t('card.detail')) ?> <?= svg_icon('arrow-up-right') ?></a>
        </div>
    </div>
</article>
