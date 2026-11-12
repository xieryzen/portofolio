<?php
/** @var array<string, mixed> $authUser */
/** @var array<string, mixed> $profile */
/** @var array<string, int> $projectStats */
/** @var array<string, int> $userStats */
/** @var int $unreadMessages */
/** @var list<array<string, mixed>> $recentProjects */
/** @var list<array<string, mixed>> $recentMessages */

$hour = (int) date('G');
$greeting = $hour < 11 ? 'Selamat pagi' : ($hour < 15 ? 'Selamat siang' : ($hour < 19 ? 'Selamat sore' : 'Selamat malam'));
$name = explode(' ', (string) ($authUser['name'] ?? 'Admin'))[0];
?>

<div class="admin-head">
    <div>
        <span class="section-label">Ringkasan</span>
        <h1 class="mt-1"><?= e($greeting) ?>, <?= e($name) ?></h1>
        <p>Berikut kondisi portofolio Anda hari ini, <?= e(format_date(date('Y-m-d'), 'd F Y')) ?>.</p>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/admin/profile')) ?>"><?= svg_icon('user', 'icon-sm') ?> Edit Profil</a>
        <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/projects/create')) ?>"><?= svg_icon('plus', 'icon-sm') ?> Tambah Project</a>
    </div>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="sc-icon"><?= svg_icon('folder') ?></span>
        <div>
            <strong><?= (int) $projectStats['published'] ?></strong>
            <span>Project terpublikasi</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-amber"><?= svg_icon('edit') ?></span>
        <div>
            <strong><?= (int) $projectStats['draft'] ?></strong>
            <span>Draft belum tampil</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-cyan"><?= svg_icon('eye') ?></span>
        <div>
            <strong><?= number_format((int) $projectStats['views'], 0, ',', '.') ?></strong>
            <span>Total kunjungan project</span>
        </div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-rose"><?= svg_icon('message') ?></span>
        <div>
            <strong><?= (int) $unreadMessages ?></strong>
            <span>Pesan belum dibaca</span>
        </div>
    </div>
</div>

<div class="grid-main-side">
    <div style="display:grid;gap:20px">
        <div class="card">
            <div class="card-head">
                <h2>Project terbaru</h2>
                <div class="actions">
                    <a class="link-arrow small" href="<?= e(app_url('/admin/projects')) ?>">Kelola <?= svg_icon('arrow-right', 'icon-sm') ?></a>
                </div>
            </div>
            <?php if ($recentProjects === []): ?>
                <div class="empty-state">
                    <?= svg_icon('folder') ?>
                    <h3>Belum ada project</h3>
                    <p>Tambahkan project pertama Anda untuk mulai mengisi portofolio.</p>
                    <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin/projects/create')) ?>">Tambah project</a>
                </div>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead>
                            <tr><th>Project</th><th>Status</th><th>Diperbarui</th><th></th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentProjects as $project): ?>
                                <?php $cover = media_url($project['cover'] ?? null); ?>
                                <tr>
                                    <td>
                                        <div class="row-title">
                                            <span class="thumb">
                                                <?php if ($cover): ?><img src="<?= e($cover) ?>" alt=""><?php else: ?><?= e(initials((string) $project['title'])) ?><?php endif; ?>
                                            </span>
                                            <span>
                                                <strong><?= e((string) $project['title']) ?></strong>
                                                <small><?= e((string) ($project['category'] ?: 'Project')) ?> · <?= e((string) ($project['year'] ?: '—')) ?></small>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($project['status'] === 'published'): ?>
                                            <span class="badge badge-success">Publik</span>
                                        <?php else: ?>
                                            <span class="badge badge-muted">Draft</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="muted small"><?= e(time_ago($project['updated_at'] ?? null)) ?></td>
                                    <td class="actions">
                                        <a class="icon-btn" href="<?= e(app_url('/admin/projects/' . $project['id'] . '/edit')) ?>" title="Edit"><?= svg_icon('edit') ?></a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Pesan terbaru</h2>
                <div class="actions">
                    <a class="link-arrow small" href="<?= e(app_url('/admin/messages')) ?>">Semua pesan <?= svg_icon('arrow-right', 'icon-sm') ?></a>
                </div>
            </div>
            <div class="card-body">
                <?php if ($recentMessages === []): ?>
                    <p class="muted small">Belum ada pesan masuk dari pengunjung.</p>
                <?php else: ?>
                    <div class="msg-list">
                        <?php foreach ($recentMessages as $message): ?>
                            <div class="msg-card <?= (int) $message['is_read'] === 0 ? 'is-unread' : '' ?>">
                                <div class="flex between" style="gap:12px;align-items:flex-start">
                                    <div class="flex" style="gap:10px;align-items:flex-start">
                                        <span class="msg-avatar"><?= e(initials((string) $message['name'])) ?></span>
                                        <div>
                                            <strong class="small"><?= e((string) $message['name']) ?></strong>
                                            <small class="muted" style="display:block"><?= e(excerpt((string) $message['body'], 90)) ?></small>
                                        </div>
                                    </div>
                                    <span class="small muted" style="white-space:nowrap"><?= e(time_ago($message['created_at'] ?? null)) ?></span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <div style="display:grid;gap:20px">
        <div class="card">
            <div class="card-head"><h2>Status profil</h2></div>
            <div class="card-body">
                <?php
                $checks = [
                    'Nama & headline' => ($profile['full_name'] ?? '') !== '' && ($profile['headline'] ?? '') !== '',
                    'Deskripsi tentang' => ($profile['bio'] ?? '') !== '',
                    'Foto profil' => !empty($profile['avatar']),
                    'Foto sampul' => !empty($profile['cover']),
                    'Kontak publik' => !empty($profile['public_email']) || !empty($profile['phone']),
                ];
                $done = count(array_filter($checks));
                $percentage = (int) round($done / max(1, count($checks)) * 100);
                ?>
                <div class="skill-top" style="margin-bottom:10px">
                    <span class="small">Kelengkapan</span>
                    <span class="small muted"><?= $percentage ?>%</span>
                </div>
                <div class="skill-bar"><i style="--level: <?= $percentage ?>%"></i></div>

                <div class="info-list" style="margin-top:18px">
                    <?php foreach ($checks as $label => $ok): ?>
                        <div class="flex" style="gap:9px;font-size:13px;color:<?= $ok ? 'var(--text)' : 'var(--muted-2)' ?>">
                            <span style="color:<?= $ok ? 'var(--success)' : 'var(--muted-2)' ?>;display:grid;place-items:center">
                                <?= svg_icon($ok ? 'check' : 'alert', 'icon-sm') ?>
                            </span>
                            <?= e($label) ?>
                        </div>
                    <?php endforeach; ?>
                </div>

                <a class="btn btn-ghost btn-sm btn-block mt-2" href="<?= e(app_url('/admin/profile')) ?>">Lengkapi profil</a>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Pengguna</h2></div>
            <div class="card-body">
                <div class="stats-strip" style="grid-template-columns:1fr 1fr;margin-top:0;border-radius:12px">
                    <div class="stat" style="padding:14px 16px">
                        <strong style="font-size:1.5rem"><?= (int) $userStats['total'] ?></strong>
                        <span>Total akun</span>
                    </div>
                    <div class="stat" style="padding:14px 16px">
                        <strong style="font-size:1.5rem"><?= (int) $userStats['admins'] ?></strong>
                        <span>Admin</span>
                    </div>
                </div>
                <a class="btn btn-ghost btn-sm btn-block mt-2" href="<?= e(app_url('/admin/users')) ?>"><?= svg_icon('users', 'icon-sm') ?> Kelola pengguna</a>
            </div>
        </div>
    </div>
</div>
