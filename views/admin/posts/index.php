<?php
/** @var array{items: list<array<string, mixed>>, total: int, pages: int, page: int} $result */
/** @var string $status */
/** @var string $search */

$filters = [
    '' => 'Semua',
    'published' => 'Publik',
    'draft' => 'Draft',
];
?>
<div class="admin-head">
    <div>
        <span class="section-label">Blog</span>
        <h1 class="mt-1">Kelola artikel</h1>
        <p><?= (int) $result['total'] ?> artikel tersimpan. Tulis catatan, atur kategori, dan publikasikan.</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/posts/create')) ?>"><?= svg_icon('plus', 'icon-sm') ?> Tulis artikel</a>
    </div>
</div>

<div class="card">
    <div class="card-head" style="flex-wrap:wrap;gap:12px">
        <div class="flex" style="gap:6px;flex-wrap:wrap">
            <?php foreach ($filters as $key => $label): ?>
                <?php $url = app_url('/admin/posts' . ($key !== '' ? '?status=' . $key : '')); ?>
                <a class="btn btn-sm <?= $status === $key ? 'btn-soft' : 'btn-ghost' ?>" href="<?= e($url) ?>" style="border-radius:999px"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <form method="get" action="<?= e(app_url('/admin/posts')) ?>" class="flex actions" style="gap:8px">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <div class="input-group" style="min-width:210px">
                <?= svg_icon('search') ?>
                <input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari artikel…" style="padding-block:8px;font-size:13px">
            </div>
            <button class="btn btn-ghost btn-sm" type="submit">Cari</button>
        </form>
    </div>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <?= svg_icon('file') ?>
            <h3>Belum ada artikel</h3>
            <p><?= $search !== '' ? 'Tidak ada artikel yang cocok dengan pencarian.' : 'Tulis artikel pertama untuk mengisi halaman blog.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/posts/create')) ?>">Tulis artikel</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Artikel</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Dilihat</th>
                        <th>Publikasi</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['items'] as $post): ?>
                    <?php
                    $cover = media_url($post['cover'] ?? null);
                    $scheduled = ($post['status'] ?? '') === 'published'
                        && !empty($post['published_at'])
                        && strtotime((string) $post['published_at']) > time();
                    ?>
                    <tr>
                        <td>
                            <div class="row-title">
                                <?php if ($cover): ?>
                                    <img class="thumb" src="<?= e($cover) ?>" alt="" loading="lazy">
                                <?php else: ?>
                                    <span class="thumb"><?= e(initials((string) $post['title'])) ?></span>
                                <?php endif; ?>
                                <span>
                                    <strong><?= e((string) $post['title']) ?></strong>
                                    <small><?= e(excerpt((string) $post['excerpt'], 80)) ?></small>
                                </span>
                            </div>
                        </td>
                        <td><?= e((string) $post['category']) ?></td>
                        <td>
                            <?php if ($scheduled): ?>
                                <span class="badge badge-accent"><?= svg_icon('clock', 'icon-sm') ?> Terjadwal</span>
                            <?php elseif (($post['status'] ?? '') === 'published'): ?>
                                <span class="badge badge-success">Publik</span>
                            <?php else: ?>
                                <span class="badge badge-muted">Draft</span>
                            <?php endif; ?>
                        </td>
                        <td><?= number_format((int) $post['views'], 0, ',', '.') ?></td>
                        <td class="small muted">
                            <?= $post['published_at'] ? e(format_date((string) $post['published_at'], 'd M Y H:i')) : '—' ?>
                        </td>
                        <td class="actions">
                            <a class="icon-btn" href="<?= e(app_url('/admin/posts/' . $post['id'] . '/edit')) ?>" aria-label="Edit artikel"><?= svg_icon('edit') ?></a>
                            <?php if (($post['status'] ?? '') === 'published'): ?>
                                <a class="icon-btn" href="<?= e(app_url('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener" aria-label="Lihat artikel"><?= svg_icon('eye') ?></a>
                            <?php endif; ?>
                            <form method="post" action="<?= e(app_url('/admin/posts/' . $post['id'] . '/delete')) ?>" data-confirm="Hapus artikel ini permanen?">
                                <?= csrf_field() ?>
                                <button class="icon-btn is-danger" type="submit" aria-label="Hapus artikel"><?= svg_icon('trash') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-body">
            <?= \App\View::partial('partials/pagination', ['result' => $result, 'query' => array_filter(['status' => $status, 'q' => $search])]) ?>
        </div>
    <?php endif; ?>
</div>
