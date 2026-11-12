<?php
/** @var array<string, mixed> $post */
/** @var list<array<string, mixed>> $related */
/** @var list<array<string, mixed>> $comments */
/** @var int $commentCount */
/** @var array<string, mixed> $profile */

use App\View;

$title = localized($post, 'title');
$cover = media_url($post['cover'] ?? null);
$published = (string) ($post['published_at'] ?? $post['created_at'] ?? '');
$date = $published !== '' ? format_date($published, 'd M Y') : '';
$tags = array_filter(array_map('trim', explode(',', (string) ($post['tags'] ?? ''))));
$paragraphs = array_filter(array_map('trim', preg_split('~\n\s*\n~', localized($post, 'content')) ?: []));
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
        <h1 class="mt-2"><?= e($title) ?></h1>

        <div class="post-meta">
            <?php if ($date !== ''): ?><span><?= svg_icon('calendar', 'icon-sm') ?> <?= e($date) ?></span><?php endif; ?>
            <span><?= svg_icon('clock', 'icon-sm') ?> <?= max(1, (int) ($post['reading_minutes'] ?? 1)) ?> <?= e(t('blog.read')) ?></span>
            <span><?= svg_icon('eye', 'icon-sm') ?> <?= number_format((int) ($post['views'] ?? 0), 0, ',', '.') ?> <?= e(t('blog.views')) ?></span>
        </div>

        <?php if ($cover): ?>
            <div class="detail-cover">
                <img src="<?= e($cover) ?>" alt="<?= e($title) ?>">
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


<section class="section-sm" id="komentar" style="border-top:1px solid var(--line)">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label"><?= e(t('comment.title')) ?></span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">
                    <?= (int) ($commentCount ?? 0) ?> <?= e(t('comment.count')) ?>
                </h2>
            </div>
        </div>

        <?php if (($comments ?? []) === []): ?>
            <p class="muted small" style="margin-bottom:22px"><?= e(t('comment.empty')) ?></p>
        <?php else: ?>
            <div class="comment-list">
                <?php foreach ($comments as $comment): ?>
                    <article class="comment-item">
                        <div class="comment-head">
                            <span class="avatar" style="width:34px;height:34px;font-size:12px"><?= e(initials((string) $comment['name'])) ?></span>
                            <div>
                                <strong><?= e((string) $comment['name']) ?></strong>
                                <small class="muted"><?= e(format_date((string) $comment['created_at'], 'd M Y')) ?></small>
                            </div>
                        </div>
                        <p><?= nl2br(e((string) $comment['body'])) ?></p>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="card" style="max-width:680px;margin-top:26px">
            <div class="card-head"><h2><?= e(t('comment.form_title')) ?></h2></div>
            <div class="card-body">
                <form method="post" action="<?= e(app_url('/blog/' . $post['slug'] . '/comment')) ?>" class="form-section" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div class="form-row">
                        <div class="field">
                            <label for="comment-name"><?= e(t('comment.name')) ?> <span class="hint">*</span></label>
                            <input class="input <?= error_for('name') ? 'has-error' : '' ?>" type="text" id="comment-name" name="name"
                                   value="<?= e(old('name')) ?>" placeholder="<?= e(t('comment.name_placeholder')) ?>" required maxlength="120">
                            <?php if ($message = error_for('name')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                        </div>
                        <div class="field">
                            <label for="comment-email"><?= e(t('comment.email')) ?></label>
                            <input class="input <?= error_for('email') ? 'has-error' : '' ?>" type="email" id="comment-email" name="email"
                                   value="<?= e(old('email')) ?>" placeholder="<?= e(t('comment.email_placeholder')) ?>" maxlength="190">
                            <?php if ($message = error_for('email')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                        </div>
                    </div>

                    <div class="field">
                        <label for="comment-body"><?= e(t('comment.body')) ?> <span class="hint">*</span></label>
                        <textarea class="textarea <?= error_for('body') ? 'has-error' : '' ?>" id="comment-body" name="body" rows="5"
                                  required maxlength="2000" placeholder="<?= e(t('comment.body_placeholder')) ?>"><?= e(old('body')) ?></textarea>
                        <?php if ($message = error_for('body')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>

                    <div class="flex between flex-wrap" style="gap:12px">
                        <span class="small muted"><?= e(t('comment.moderation')) ?></span>
                        <button class="btn btn-primary" type="submit"><?= e(t('comment.submit')) ?> <?= svg_icon('arrow-right', 'icon-sm') ?></button>
                    </div>
                </form>
            </div>
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
