<?php
/** @var array<string, mixed> $post */
/** @var list<array<string, mixed>> $related */
/** @var array<string, mixed> $profile */

use App\View;

$cover = media_url($post['cover'] ?? null);
$published = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
$date = $published !== '' ? format_date($published, 'd M Y') : '';
$tags = array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? ''))));
$paragraphs = array_filter(array_map('trim', preg_split('~\n\s*\n~', (string) ($post['content'] ?? '')) ?: []));
?>
<section class="page-hero">
    <div class="container">
        <a class="back-link" href="<?= e(app_url('/blog')) ?>"><?= svg_icon('arrow-left') ?> <?= e(t('blog.back')) ?></a>

        <span class="eyebrow">
            <span class="dot"></span>
            <?= e((string) ($post['category'] ?: t('blog.label'))) ?>
            <?php if ((int) ($post['is_featured'] ?? 0) === 1): ?>
                · <?= e(t('blog.featured')) ?>
            <?php endif; ?>
        </span>
        <h1 class="mt-2"><?= e((string) $post['title']) ?></h1>

        <div class="post-meta">
            <?php if ($date !== ''): ?><span><?= svg_icon('calendar', 'icon-sm') ?> <?= e($date) ?></span><?php endif; ?>
            <span><?= svg_icon('clock', 'icon-sm') ?> <?= max(1, (int) ($post['reading_minutes'] ?? 1)) ?> <?= e(t('blog.read')) ?></span>
            <span><?= svg_icon('eye', 'icon-sm') ?> <?= number_format((int) ($post['views'] ?? 0), 0, ',', '.') ?> <?= e(t('blog.views')) ?></span>
        </div>

        <?php if ($cover): ?>
            <div class="detail-cover">
                <img src="<?= e($cover) ?>" alt="<?= e((string) $post['title']) ?>">
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="detail-body">
            <div class="prose">
                <?php if ($paragraphs !== []): ?>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p><?= nl2br(e($paragraph)) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">—</p>
                <?php endif; ?>

                <?php if ($tags !== []): ?>
                    <div class="chips" style="margin-top:22px">
                        <?php foreach ($tags as $tag): ?>
                            <span class="chip">#<?= e($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="detail-side">
                <div class="side-card">
                    <h4><?= e(t('blog.cta_title')) ?></h4>
                    <p class="small muted" style="margin-bottom:14px"><?= e(t('blog.cta_desc')) ?></p>
                    <a class="btn btn-soft btn-sm btn-block" href="<?= e(app_url('/#kontak')) ?>">
                        <?= svg_icon('mail') ?> <?= e(t('blog.cta_button')) ?>
                    </a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php if ($related !== []): ?>
<section class="section-sm" style="border-top:1px solid var(--line)">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label"><?= e(t('blog.label')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)"><?= e(t('blog.related')) ?></h2>
            </div>
            <a class="link-arrow" href="<?= e(app_url('/blog')) ?>"><?= e(t('blog.view_all')) ?> <?= svg_icon('arrow-right') ?></a>
        </div>
        <div class="project-grid">
            <?php foreach ($related as $item): ?>
                <?= View::partial('partials/post-card', ['post' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
