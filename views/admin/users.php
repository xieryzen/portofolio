<?php
/** @var array{items: list<array<string, mixed>>, total: int, pages: int, page: int} $result */
/** @var string $search */
/** @var array<string, int> $stats */
/** @var array<string, mixed>|null $authUser */
?>
<div class="admin-head">
    <div>
        <span class="section-label">Pengguna</span>
        <h1 class="mt-1">Kelola akun</h1>
        <p><?= (int) $stats['total'] ?> akun terdaftar · <?= (int) $stats['admins'] ?> admin.</p>
    </div>
    <div class="actions">
        <form method="get" action="<?= e(app_url('/admin/users')) ?>" class="flex" style="gap:8px">
            <div class="input-group" style="min-width:220px">
                <?= svg_icon('search') ?>
                <input class="input" type="search" name="q" value="<?= e($search) ?>" placeholder="Cari nama atau email…" style="padding-block:8px;font-size:13px">
            </div>
            <button class="btn btn-ghost btn-sm" type="submit">Cari</button>
        </form>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="sc-icon"><?= svg_icon('users') ?></span>
        <div><strong><?= (int) $stats['total'] ?></strong><span>Total akun</span></div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-cyan"><?= svg_icon('dashboard') ?></span>
        <div><strong><?= (int) $stats['admins'] ?></strong><span>Admin & owner</span></div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-rose"><?= svg_icon('lock') ?></span>
        <div><strong><?= (int) $stats['inactive'] ?></strong><span>Nonaktif</span></div>
    </div>
</div>

<div class="card">
    <?php if ($result['items'] === []): ?>
        <div class="empty-state">
            <?= svg_icon('users') ?>
            <h3>Tidak ada pengguna</h3>
            <p>Belum ada akun yang cocok dengan filter ini.</p>
        </div>
    <?php else: ?>
        <div class="table-wrap">
            <table class="table">
                <thead>
                    <tr><th>Pengguna</th><th>Role</th><th>Status</th><th>Terakhir masuk</th><th>Bergabung</th><th style="text-align:right">Aksi</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($result['items'] as $user): ?>
                        <?php $isSelf = (int) $user['id'] === (int) ($authUser['id'] ?? 0); ?>
                        <tr>
                            <td>
                                <div class="row-title">
                                    <span class="thumb" style="border-radius:50%;width:40px;height:40px;font-size:13px"><?= e(initials((string) $user['name'])) ?></span>
                                    <span>
                                        <strong><?= e((string) $user['name']) ?> <?= $isSelf ? '<span class="badge badge-accent" style="margin-left:4px">Anda</span>' : '' ?></strong>
                                        <small><?= e((string) $user['email']) ?></small>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <?php if ($user['role'] === 'owner'): ?>
                                    <span class="badge badge-amber">Owner</span>
                                <?php elseif ($user['role'] === 'admin'): ?>
                                    <span class="badge badge-accent">Admin</span>
                                <?php else: ?>
                                    <span class="badge badge-muted">User</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ((int) $user['is_active'] === 1): ?>
                                    <span class="badge badge-success">Aktif</span>
                                <?php else: ?>
                                    <span class="badge badge-rose">Nonaktif</span>
                                <?php endif; ?>
                            </td>
                            <td class="small muted"><?= e(time_ago($user['last_login_at'] ?? null)) ?></td>
                            <td class="small muted"><?= e(format_date($user['created_at'] ?? null, 'd M Y')) ?></td>
                            <td class="actions">
                                <?php if (!$isSelf && $user['role'] !== 'owner'): ?>
                                    <form method="post" action="<?= e(app_url('/admin/users/' . $user['id'] . '/role')) ?>" class="flex" style="display:inline-flex;gap:6px">
                                        <?= csrf_field() ?>
                                        <select class="select" name="role" style="padding:6px 28px 6px 10px;font-size:12.5px;width:auto">
                                            <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                                            <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <button class="icon-btn" type="submit" title="Simpan role"><?= svg_icon('check') ?></button>
                                    </form>

                                    <form method="post" action="<?= e(app_url('/admin/users/' . $user['id'] . '/status')) ?>" style="display:inline"
                                        data-confirm="<?= (int) $user['is_active'] === 1 ? 'Akun tidak akan bisa masuk sampai diaktifkan kembali.' : 'Akun akan dapat masuk kembali.' ?>">
                                        <?= csrf_field() ?>
                                        <button class="icon-btn" type="submit" title="<?= (int) $user['is_active'] === 1 ? 'Nonaktifkan' : 'Aktifkan' ?>">
                                            <?= svg_icon((int) $user['is_active'] === 1 ? 'lock' : 'check') ?>
                                        </button>
                                    </form>

                                    <form method="post" action="<?= e(app_url('/admin/users/' . $user['id'] . '/delete')) ?>" style="display:inline"
                                        data-confirm="Akun &quot;<?= e((string) $user['email']) ?>&quot; akan dihapus permanen." data-confirm-title="Hapus akun?">
                                        <?= csrf_field() ?>
                                        <button class="icon-btn is-danger" type="submit" title="Hapus"><?= svg_icon('trash') ?></button>
                                    </form>
                                <?php else: ?>
                                    <span class="small muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?= \App\View::partial('partials/pagination', ['result' => $result, 'query' => ['q' => $search]]) ?>
    <?php endif; ?>
</div>

<p class="small muted mt-2">
    Catatan: akun owner tidak dapat diubah role-nya atau dihapus. Admin dapat mengelola konten, namun pengaturan sensitif tetap milik owner.
</p>
