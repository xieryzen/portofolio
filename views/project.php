<?php
/** @var array<string, mixed> $project */
/** @var list<array<string, mixed>> $images */
/** @var list<array<string, mixed>> $related */
/** @var list<array<string, mixed>> $releases */
/** @var array<string, mixed> $profile */

use App\View;

$releases = $releases ?? [];
$cover = media_url($project['cover'] ?? null);
$tags = array_filter(array_map('trim', explode(',', (string) ($project['tech_stack'] ?? ''))));
$paragraphs = array_filter(array_map('trim', preg_split('~\n\s*\n~', (string) ($project['description'] ?? '')) ?: []));
$meta = array_filter([
    t('project.category') => (string) ($project['category'] ?? ''),
    t('project.client') => (string) ($project['client'] ?? ''),
    t('project.role') => (string) ($project['role'] ?? ''),
    t('project.year') => (string) ($project['year'] ?? ''),
], static fn (string $value): bool => $value !== '');

$caseBlocks = array_filter([
    t('project.case_challenge') => trim((string) ($project['case_challenge'] ?? '')),
    t('project.case_solution') => trim((string) ($project['case_solution'] ?? '')),
    t('project.case_result') => trim((string) ($project['case_result'] ?? '')),
], static fn (string $value): bool => $value !== '');
$caseMetrics = [];
foreach (preg_split('~\R~', (string) ($project['case_metrics'] ?? '')) ?: [] as $line) {
    $line = trim($line);
    if ($line === '') {
        continue;
    }
    [$label, $value] = array_pad(array_map('trim', explode('|', $line, 2)), 2, '');
    if ($value === '') {
        $caseMetrics[] = ['label' => '', 'value' => $label];
    } else {
        $caseMetrics[] = ['label' => $label, 'value' => $value];
    }
}
?>

<section class="page-hero">
    <div class="container">
        <a class="back-link" href="<?= e(app_url('/#karya')) ?>"><?= svg_icon('arrow-left') ?> <?= e(t('project.back')) ?></a>

        <span class="eyebrow"><span class="dot"></span> <?= e((string) ($project['category'] ?: t('project.fallback'))) ?></span>
        <h1 class="mt-2"><?= e((string) $project['title']) ?></h1>
        <?php if (!empty($project['summary'])): ?>
            <p class="lead"><?= e((string) $project['summary']) ?></p>
        <?php endif; ?>

        <?php if ($meta !== []): ?>
            <div class="detail-meta" data-reveal>
                <?php foreach ($meta as $label => $value): ?>
                    <div>
                        <small><?= e($label) ?></small>
                        <span><?= e($value) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="detail-cover">
            <?php if ($cover): ?>
                <img src="<?= e($cover) ?>" alt="<?= e(t('project.cover_alt')) ?> <?= e((string) $project['title']) ?>">
            <?php else: ?>
                <div class="cover-fallback"><?= e(initials((string) $project['title'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="detail-body">
            <div class="prose">
                <span class="section-label"><?= e(t('project.about')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.4rem,2.4vw,1.9rem);margin-bottom:20px"><?= e(t('project.summary_title')) ?></h2>

                <?php if ($paragraphs !== []): ?>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p><?= nl2br(e($paragraph)) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted"><?= e(t('project.no_desc')) ?></p>
                <?php endif; ?>

                <?php if ($images !== []): ?>
                    <div class="mt-3">
                        <span class="section-label"><?= e(t('gallery.label')) ?></span>
                        <h2 class="section-title" style="font-size:clamp(1.4rem,2.4vw,1.9rem);margin:12px 0 22px"><?= e(t('project.gallery')) ?></h2>
                        <div class="doc-grid">
                            <?php foreach ($images as $image): ?>
                                <button type="button" class="doc-item"
                                    data-lightbox="<?= e(media_url((string) $image['file_name']) ?? '') ?>"
                                    data-caption="<?= e((string) ($image['caption'] ?: $project['title'])) ?>">
                                    <img src="<?= e(media_url((string) $image['file_name']) ?? '') ?>" alt="<?= e((string) ($image['caption'] ?: $project['title'])) ?>" loading="lazy">
                                    <?php if (!empty($image['caption'])): ?>
                                        <figcaption><?= e((string) $image['caption']) ?></figcaption>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="detail-side">
                <?php if ($tags !== []): ?>
                    <div class="side-card">
                        <h4><?= e(t('project.tech')) ?></h4>
                        <div class="tag-list">
                            <?php foreach ($tags as $tag): ?>
                                <span class="tag"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['project_url']) || !empty($project['repo_url'])): ?>
                    <div class="side-card">
                        <h4><?= e(t('project.links')) ?></h4>
                        <div class="side-actions">
                            <?php if (!empty($project['project_url'])): ?>
                                <a class="btn btn-primary btn-sm" href="<?= e((string) $project['project_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= svg_icon('external') ?> <?= e(t('project.visit')) ?>
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($project['repo_url'])): ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e((string) $project['repo_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= svg_icon('github') ?> <?= e(t('project.repo')) ?>
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="side-card">
                    <h4><?= e(t('project.cta_title')) ?></h4>
                    <p class="small muted" style="margin-bottom:14px">
                        <?= e(t('project.cta_desc')) ?>
                    </p>
                    <a class="btn btn-soft btn-sm btn-block" href="<?= e(app_url('/#kontak')) ?>"><?= svg_icon('mail') ?> <?= e(t('project.cta_button')) ?></a>
                </div>
            </aside>
        </div>
    </div>
</section>



<?php if ($caseBlocks !== [] || $caseMetrics !== []): ?>
<section class="section-sm" style="border-top:1px solid var(--line)" id="studi-kasus">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label"><?= e(t('project.case_label')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)"><?= e(t('project.case_title')) ?></h2>
            </div>
        </div>

        <div class="case-grid">
            <?php foreach ($caseBlocks as $label => $text): ?>
                <article class="case-card" data-reveal>
                    <h3><?= e($label) ?></h3>
                    <p><?= nl2br(e($text)) ?></p>
                </article>
            <?php endforeach; ?>
        </div>

        <?php if ($caseMetrics !== []): ?>
            <div class="case-metrics" data-reveal>
                <?php foreach ($caseMetrics as $metric): ?>
                    <div class="case-metric">
                        <strong><?= e($metric['value']) ?></strong>
                        <?php if ($metric['label'] !== ''): ?>
                            <span><?= e($metric['label']) ?></span>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>
<?php endif; ?>

<?php if ($releases !== []): ?>
<section class="section-sm" style="border-top:1px solid var(--line)" id="unduhan">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label"><?= e(t('project.downloads_label')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)"><?= e(t('project.downloads_title')) ?></h2>
                <p class="section-lead"><?= e(t('project.downloads_desc')) ?></p>
            </div>
            <span class="badge badge-accent"><?= svg_icon('package', 'icon-sm') ?> <?= e(t('project.latest_version')) ?> v<?= e((string) $releases[0]['version']) ?></span>
        </div>

        <div class="release-list">
            <?php foreach ($releases as $index => $release): ?>
                <article class="release-item">
                    <div class="release-main">
                        <div class="release-version">
                            <strong>v<?= e((string) $release['version']) ?></strong>
                            <?php if ($index === 0): ?>
                                <span class="badge badge-success"><?= e(t('project.latest')) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($release['title'])): ?>
                                <span class="small muted"><?= e((string) $release['title']) ?></span>
                            <?php endif; ?>
                        </div>

                        <div class="release-meta">
                            <span><?= svg_icon('calendar', 'icon-sm') ?> <?= e(format_date($release['released_at'], 'd M Y')) ?></span>
                            <?php if ($release['file_name']): ?>
                                <span><?= svg_icon('package', 'icon-sm') ?> <?= e(format_bytes((int) $release['file_size'])) ?></span>
                            <?php endif; ?>
                            <span><?= svg_icon('download', 'icon-sm') ?> <?= number_format((int) $release['downloads'], 0, ',', '.') ?> <?= e(t('project.downloads_count')) ?></span>
                        </div>

                        <?php if (!empty($release['notes'])): ?>
                            <div class="release-notes"><?= nl2br(e((string) $release['notes'])) ?></div>
                        <?php endif; ?>
                    </div>

                    <div class="release-actions">
                        <?php if ($release['file_name']): ?>
                            <a class="btn btn-primary btn-sm" href="<?= e(app_url('/download/' . $release['id'])) ?>" rel="nofollow">
                                <?= svg_icon('download', 'icon-sm') ?> <?= e(t('project.download')) ?> <?= e(release_file_label($release['original_name'] ?? null, $release['file_name'] ?? null)) ?>
                            </a>
                        <?php else: ?>
                            <span class="badge badge-muted"><?= e(t('project.no_file')) ?></span>
                        <?php endif; ?>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if ($related !== []): ?>
<section class="section-sm" style="border-top:1px solid var(--line)">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label"><?= e(t('project.other')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)"><?= e(t('project.other_title')) ?></h2>
            </div>
            <a class="link-arrow" href="<?= e(app_url('/#karya')) ?>"><?= e(t('project.all_work')) ?> <?= svg_icon('arrow-right') ?></a>
        </div>
        <div class="project-grid">
            <?php foreach ($related as $item): ?>
                <?= View::partial('partials/project-card', ['project' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
