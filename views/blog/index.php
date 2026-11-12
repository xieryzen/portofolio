<?php
/** @var array{items: list<array<string, mixed>>, total: int, pages: int, page: int} $result */
/** @var list<array{category: string, total: int}> $categories */
/** @var string $category */
/** @var string $searchTerm */

use App\View;

$query = array_filter([
    'kategori' => $category,
    'q' => $searchTerm,
], static fn (string $value): bool => $value !== '');
?>
<section class="page-hero">
    <div class="container">
        <span class="section-label"><?= e(t('blog.label')) ?></span>
        <h1 class="mt-1"><?= e(t('blog.title')) ?></h1>
        <p class="lead"><?= e(t('blog.desc')) ?></p>

        <form class="project-search" method="get" action="<?= e(app_url('/blog')) ?>" role="search" style="margin-top:22px">
            <?php if ($category !== ''): ?>
                <input type="hidden" name="kategori" value="<?= e($category) ?>">
            <?php endif; ?>
            <div class="input-group">
                <?= svg_icon('search') ?>
                <input class="input" type="search" name="q" value="<?= e($searchTerm) ?>"
                       placeholder="<?= e(t('blog.search_placeholder')) ?>" maxlength="80"
                       aria-label="<?= e(t('blog.search_placeholder')) ?>">
            </div>
            <button class="btn btn-primary" type="submit"><?= e(t('blog.search')) ?></button>
            <?php if ($searchTerm !== '' || $category !== ''): ?>
                <a class="btn btn-ghost" href="<?= e(app_url('/blog')) ?>"><?= e(t('work.reset')) ?></a>
            <?php endif; ?>
        </form>

        <?php if ($categories !== []): ?>
            <div class="chips" style="margin-top:16px">
                <a class="chip <?= $category === '' ? 'is-active' : '' ?>" href="<?= e(app_url('/blog')) ?>"><?= e(t('blog.all')) ?></a>
                <?php foreach ($categories as $item): ?>
                    <a class="chip <?= $category === (string) $item['category'] ? 'is-active' : '' ?>"
                       href="<?= e(app_url('/blog?kategori=' . rawurlencode((string) $item['category']))) ?>">
                        <?= e((string) $item['category']) ?> <span class="muted">(<?= (int) $item['total'] ?>)</span>
                    </a>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <?php if ($searchTerm !== ''): ?>
            <p class="small muted" style="margin-bottom:18px">
                <?= (int) $result['total'] ?> <?= e(t('blog.matches')) ?> “<?= e($searchTerm) ?>”.
            </p>
        <?php endif; ?>

        <?php if ($result['items'] === []): ?>
            <div class="card">
                <div class="empty-state">
                    <?= svg_icon($searchTerm !== '' ? 'search' : 'file') ?>
                    <h3><?= e($searchTerm !== '' ? t('blog.no_match_title') : t('blog.empty_title')) ?></h3>
                    <p><?= e($searchTerm !== '' ? t('blog.no_match_desc') : t('blog.empty_desc')) ?></p>
                </div>
            </div>
        <?php else: ?>
            <div class="project-grid">
                <?php foreach ($result['items'] as $post): ?>
                    <?= View::partial('partials/post-card', ['post' => $post]) ?>
                <?php endforeach; ?>
            </div>

            <?= View::partial('partials/pagination', ['result' => $result, 'query' => $query]) ?>
        <?php endif; ?>
    </div>
</section>
