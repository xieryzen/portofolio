<?php
/**
 * @var array{page: int, pages: int} $result
 * @var array<string, string> $query
 */
$query = $query ?? [];
$build = static function (int $page) use ($query): string {
    $params = array_filter(array_merge($query, ['page' => (string) $page]), static fn ($v) => $v !== '');

    return app_url(current_path() . ($params ? '?' . http_build_query($params) : ''));
};

if (($result['pages'] ?? 1) <= 1) {
    return;
}
?>
<nav class="pagination" aria-label="Navigasi halaman">
    <a class="<?= $result['page'] <= 1 ? 'is-disabled' : '' ?>" href="<?= e($build(max(1, $result['page'] - 1))) ?>" aria-label="Halaman sebelumnya"><?= svg_icon('arrow-left', 'icon-sm') ?></a>

    <?php for ($i = 1; $i <= $result['pages']; $i++): ?>
        <?php if ($i === 1 || $i === $result['pages'] || abs($i - $result['page']) <= 1): ?>
            <?php if ($i === $result['page']): ?>
                <span class="is-current" aria-current="page"><?= $i ?></span>
            <?php else: ?>
                <a href="<?= e($build($i)) ?>"><?= $i ?></a>
            <?php endif; ?>
        <?php elseif (abs($i - $result['page']) === 2): ?>
            <span class="is-disabled">…</span>
        <?php endif; ?>
    <?php endfor; ?>

    <a class="<?= $result['page'] >= $result['pages'] ? 'is-disabled' : '' ?>" href="<?= e($build(min($result['pages'], $result['page'] + 1))) ?>" aria-label="Halaman berikutnya"><?= svg_icon('arrow-right', 'icon-sm') ?></a>
</nav>
