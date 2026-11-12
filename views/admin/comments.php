<?php
/** @var array{items: list<array<string, mixed>>, total: int, pages: int, page: int} $result */
/** @var string $status */
/** @var int $pending */

$filters = [
    'pending' => 'Menunggu',
    'approved' => 'Disetujui',
    'all' => 'Semua',
];
?>
<div class="admin-head">
    <div>
        <span class="section-label">Blog</span>
        <h1 class="mt-1">Komentar artikel</h1>
        <p><?= (int) $pending ?> komentar menunggu moderasi. Setujui agar tampil di halaman artikel.</p>
    </div>
    <span class="badge"><?= (int) $result['total'] ?> entri</span>
</div>

<div class="card">
    <div class="card-head" style="flex-wrap:wrap;gap:12px">
        <div class="flex" style="gap:6px;flex-wrap:wrap">
            <?php foreach ($filters as $key => $label): ?>
                <?php $url = app_url('/admin/comments?status=' . $key); ?>
                <a class="btn btn-sm <?= $status === $key ? 'btn-soft' : 'btn-ghost' ?>" href="<?= e($url) ?>" style="border-radius:999px">
                    <?= e($label) ?><?= $key === 'pending' && $pending > 0 ? ' (' . (int) $pending . ')' : '' ?>
                </a>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <?= svg_icon('message') ?>
            <h3>Tidak ada komentar</h3>
            <p><?= $status === 'pending' ? 'Semua komentar sudah dimoderasi.' : 'Belum ada komentar pada filter ini.' ?></p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Pengirim</th>
                        <th>Komentar</th>
                        <th>Artikel</th>
                        <th>Tanggal</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                <?php foreach ($result['items'] as $comment): ?>
                    <tr>
                        <td>
                            <div class="row-title">
                                <span class="thumb" style="border-radius:50%"><?= e(initials((string) $comment['name'])) ?></span>
                                <span>
                                    <strong><?= e((string) $comment['name']) ?></strong>
                                    <small><?= e((string) $comment['email']) ?: '—' ?></small>
                                </span>
                            </div>
                        </td>
                        <td style="max-width:360px">
                            <span class="small"><?= e(excerpt((string) $comment['body'], 160)) ?></span>
                            <div style="margin-top:6px">
                                <?php if ((int) $comment['is_approved'] === 1): ?>
                                    <span class="badge badge-success">Disetujui</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Menunggu</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td class="small">
                            <?php if (!empty($comment['post_slug'])): ?>
                                <a href="<?= e(app_url('/blog/' . $comment['post_slug'])) ?>" target="_blank" rel="noopener">
                                    <?= e(excerpt((string) $comment['post_title'], 60)) ?>
                                </a>
                            <?php else: ?>
                                <span class="muted">—</span>
                            <?php endif; ?>
                        </td>
                        <td class="small muted" style="white-space:nowrap"><?= e(format_date((string) $comment['created_at'], 'd M Y H:i')) ?></td>
                        <td class="actions">
                            <?php if ((int) $comment['is_approved'] !== 1): ?>
                                <form method="post" action="<?= e(app_url('/admin/comments/' . $comment['id'] . '/approve')) ?>">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn" type="submit" aria-label="Setujui komentar" title="Setujui"><?= svg_icon('check') ?></button>
                                </form>
                            <?php endif; ?>
                            <form method="post" action="<?= e(app_url('/admin/comments/' . $comment['id'] . '/delete')) ?>" data-confirm="Hapus komentar ini permanen?">
                                <?= csrf_field() ?>
                                <button class="icon-btn is-danger" type="submit" aria-label="Hapus komentar" title="Hapus"><?= svg_icon('trash') ?></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <div class="card-body">
            <?= \App\View::partial('partials/pagination', ['result' => $result, 'query' => ['status' => $status]]) ?>
        </div>
    <?php endif; ?>
</div>
