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
        <span class="section-label">Portofolio</span>
        <h1 class="mt-1">Kelola project</h1>
        <p><?= (int) $result['total'] ?> project tersimpan. Kelola judul, deskripsi, cover, dan dokumentasi.</p>
    </div>
    <div class="actions">
        <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/projects/create')) ?>"><?= svg_icon('plus', 'icon-sm') ?> Tambah Project</a>
    </div>
</div>

<div class="card">
    <div class="card-head" style="flex-wrap:wrap;gap:12px">
        <div class="flex" style="gap:6px;flex-wrap:wrap">
            <?php foreach ($filters as $key => $label): ?>
                <?php $url = app_url('/admin/projects' . ($key !== '' ? '?status=' . $key : '')); ?>
                <a class="btn btn-sm <?= $status === $key ? 'btn-soft' : 'btn-ghost' ?>" href="<?= e($url) ?>" style="border-radius:999px"><?= e($label) ?></a>
            <?php endforeach; ?>
        </div>

        <form method="get" action="<?= e(app_url('/admin/projects')) ?>" class="flex actions" style="gap:8px">
            <?php if ($status !== ''): ?><input type="hidden" name="status" value="<?= e($status) ?>"><?php endif; ?>
            <div class="input-group" style="min-width:210px">
                <?= svg_icon('search') ?>
                <input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari project…" style="padding-block:8px;font-size:13px">
            </div>
            <button class="btn btn-ghost btn-sm" type="submit">Cari</button>
        </form>
    </div>

    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <?= svg_icon('folder') ?>
            <h3>Belum ada project</h3>
            <p><?= $search !== '' ? 'Tidak ada project yang cocok dengan pencarian.' : 'Tambahkan project pertama untuk mengisi halaman portofolio.' ?></p>
            <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/projects/create')) ?>">Tambah project</a>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr>
                        <th>Project</th>
                        <th>Kategori</th>
                        <th>Status</th>
                        <th>Dilihat</th>
                        <th>Diperbarui</th>
                        <th style="text-align:right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $project): ?>
                        <?php $cover = media_url($project['cover'] ?? null); ?>
                        <tr>
                            <td>
                                <div class="row-title">
                                    <span class="thumb">
                                        <?php if ($cover): ?><img src="<?= e($cover) ?>" alt=""><?php else: ?><?= e(initials((string) $project['title'])) ?><?php endif; ?>
                                    </span>
                                    <span>
                                        <strong>
                                            <?= e((string) $project['title']) ?>
                                            <?php if ((int) $project['is_featured'] === 1): ?>
                                                <span title="Unggulan" style="color:var(--warning)"><?= svg_icon('star', 'icon-sm') ?></span>
                                            <?php endif; ?>
                                        </strong>
                                        <small>/project/<?= e((string) $project['slug']) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td class="small muted"><?= e((string) ($project['category'] ?: '—')) ?></td>
                            <td>
                                <?php if ($project['status'] === 'published'): ?>
                                    <span class="badge badge-success">Publik</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">Draft</span>
                                <?php endif; ?>
                            </td>
                            <td class="small muted"><?= number_format((int) $project['views'], 0, ',', '.') ?></td>
                            <td class="small muted"><?= e(time_ago($project['updated_at'] ?? null)) ?></td>
                            <td class="actions">
                                <a class="icon-btn" href="<?= e(app_url('/project/' . $project['slug'])) ?>" target="_blank" rel="noopener" title="Lihat"><?= svg_icon('eye') ?></a>
                                <a class="icon-btn" href="<?= e(app_url('/admin/projects/' . $project['id'] . '/edit')) ?>" title="Edit"><?= svg_icon('edit') ?></a>
                                <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/status')) ?>" style="display:inline">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn" type="submit" title="<?= $project['status'] === 'published' ? 'Jadikan draft' : 'Publikasikan' ?>">
                                        <?= svg_icon($project['status'] === 'published' ? 'eye' : 'check') ?>
                                    </button>
                                </form>
                                <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/delete')) ?>" style="display:inline"
                                    data-confirm="Project &quot;<?= e((string) $project['title']) ?>&quot; beserta seluruh gambar dokumentasinya akan dihapus permanen."
                                    data-confirm-title="Hapus project?">
                                    <?= csrf_field() ?>
                                    <button class="icon-btn is-danger" type="submit" title="Hapus"><?= svg_icon('trash') ?></button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= \App\View::partial('partials/pagination', [
            'result' => $result,
            'query' => ['status' => $status, 'q' => $search],
        ]) ?>
    <?php endif; ?>
</div>
