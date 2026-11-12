<?php
/** @var array{items: list<array<string, mixed>>, total: int, pages: int, page: int} $result */

$labels = [
    'login' => ['Masuk', 'var(--success)'],
    'login_2fa' => ['Masuk 2FA', 'var(--success)'],
    'login_failed' => ['Login gagal', 'var(--danger)'],
    'logout' => ['Keluar', 'var(--muted-2)'],
    '2fa_enabled' => ['2FA aktif', 'var(--accent)'],
    '2fa_disabled' => ['2FA nonaktif', 'var(--warning)'],
    '2fa_blocked' => ['2FA diblokir', 'var(--danger)'],
    '2fa_recovery_regenerated' => ['Kode pemulihan baru', 'var(--accent)'],
    'recovery_used' => ['Kode pemulihan dipakai', 'var(--warning)'],
    'password_changed' => ['Kata sandi diubah', 'var(--accent)'],
    'settings_updated' => ['Pengaturan diubah', 'var(--muted)'],
    'account_updated' => ['Akun diubah', 'var(--muted)'],
    'backup_restored' => ['Restore cadangan', 'var(--danger)'],
];
?>
<div class="admin-head">
    <div>
        <span class="section-label">Sistem</span>
        <h1 class="mt-1">Log aktivitas</h1>
        <p>Riwayat masuk, perubahan keamanan, dan aksi penting lainnya. Khusus owner.</p>
    </div>
    <span class="badge"><?= (int) $result['total'] ?> entri</span>
</div>

<div class="card">
    <div class="card-head"><h2>40 aktivitas terbaru</h2></div>
    <div class="card-body">
        <?php if ($result['items'] === []): ?>
            <div class="empty-state" style="padding:30px 10px">
                <?= svg_icon('clock') ?>
                <h3>Belum ada aktivitas</h3>
                <p>Setiap login dan perubahan keamanan akan tercatat di sini.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Aksi</th>
                            <th>Akun</th>
                            <th>Keterangan</th>
                            <th>IP</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($result['items'] as $item): ?>
                        <?php
                        $action = (string) $item['action'];
                        [$label, $color] = $labels[$action] ?? [$action, 'var(--muted-2)'];
                        ?>
                        <tr>
                            <td style="white-space:nowrap"><?= e((string) $item['created_at']) ?></td>
                            <td>
                                <span class="badge" style="background:color-mix(in srgb, <?= $color ?> 16%, transparent);color:<?= $color ?>">
                                    <?= e($label) ?>
                                </span>
                            </td>
                            <td><?= e((string) $item['actor']) ?: '—' ?></td>
                            <td><?= e((string) $item['description']) ?></td>
                            <td><code style="font-size:12px"><?= e((string) $item['ip_address']) ?></code></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ((int) $result['pages'] > 1): ?>
                <nav class="pagination" style="margin-top:16px" aria-label="Halaman log">
                    <?php for ($page = 1; $page <= (int) $result['pages']; $page++): ?>
                        <?php if ($page === (int) $result['page']): ?>
                            <span class="is-current"><?= $page ?></span>
                        <?php else: ?>
                            <a href="<?= e(app_url('/admin/activity?halaman=' . $page)) ?>"><?= $page ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>
