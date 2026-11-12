<?php
/** @var array{daily: list<array{date: string, total: int}>, totals: array{today: int, week: int, month: int, all: int, bots: int}, pages: list<array{path: string, total: int}>, referrers: list<array{referrer: string, total: int}>} $stats */
/** @var int $days */
/** @var list<array<string, mixed>> $popularProjects */
/** @var list<array<string, mixed>> $popularPosts */

$max = 0;
foreach ($stats['daily'] as $day) {
    $max = max($max, $day['total']);
}
$max = max(1, $max);
$months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
$label = static function (string $date) use ($months): string {
    $time = strtotime($date) ?: time();

    return date('j', $time) . ' ' . ($months[(int) date('n', $time)] ?? '');
};
$ranges = [7 => '7 hari', 14 => '14 hari', 30 => '30 hari', 90 => '90 hari'];
?>
<div class="admin-head">
    <div>
        <span class="section-label">Sistem</span>
        <h1 class="mt-1">Statistik kunjungan</h1>
        <p>Hanya halaman publik yang dihitung. Bot dan crawler difilter, tidak ada IP atau cookie yang disimpan.</p>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/admin/stats/export')) ?>"><?= svg_icon('download', 'icon-sm') ?> Ekspor CSV</a>
        <span class="badge"><?= (int) $days ?> hari terakhir</span>
    </div>
</div>

<div class="flex" style="gap:6px;flex-wrap:wrap;margin-bottom:18px">
    <?php foreach ($ranges as $value => $text): ?>
        <a class="btn btn-sm <?= $days === $value ? 'btn-soft' : 'btn-ghost' ?>"
           href="<?= e(app_url('/admin/stats?hari=' . $value)) ?>" style="border-radius:999px"><?= e($text) ?></a>
    <?php endforeach; ?>
</div>

<div class="stat-grid">
    <div class="stat-card">
        <span class="sc-icon"><?= svg_icon('eye') ?></span>
        <div><strong><?= number_format($stats['totals']['today'], 0, ',', '.') ?></strong><span>Hari ini</span></div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-cyan"><?= svg_icon('calendar') ?></span>
        <div><strong><?= number_format($stats['totals']['week'], 0, ',', '.') ?></strong><span>7 hari</span></div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-violet"><?= svg_icon('chart') ?></span>
        <div><strong><?= number_format($stats['totals']['month'], 0, ',', '.') ?></strong><span>30 hari</span></div>
    </div>
    <div class="stat-card">
        <span class="sc-icon is-amber"><?= svg_icon('sparkles') ?></span>
        <div><strong><?= number_format($stats['totals']['all'], 0, ',', '.') ?></strong><span>Total</span></div>
    </div>
</div>

<div class="card" style="margin-bottom:20px">
    <div class="card-head">
        <h2>Kunjungan <?= (int) $days ?> hari terakhir</h2>
        <span class="small muted"><?= number_format($stats['totals']['bots'], 0, ',', '.') ?> kunjungan bot difilter</span>
    </div>
    <div class="card-body">
        <div class="visit-chart" role="img" aria-label="Grafik kunjungan harian">
            <?php foreach ($stats['daily'] as $day): ?>
                <?php $height = (int) max(4, round(($day['total'] / $max) * 120)); ?>
                <div class="visit-col" style="min-width:<?= $days > 30 ? '22px' : '34px' ?>"
                     title="<?= e($label($day['date'])) ?>: <?= (int) $day['total'] ?> kunjungan">
                    <?php if ($days <= 30): ?>
                        <span class="visit-value"><?= (int) $day['total'] ?></span>
                    <?php endif; ?>
                    <span class="visit-bar" style="height:<?= $height ?>px"></span>
                    <?php if ($days <= 30 || (int) date('j', strtotime($day['date']) ?: time()) % 7 === 1): ?>
                        <span class="visit-date"><?= e($label($day['date'])) ?></span>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-head"><h2>Halaman populer (30 hari)</h2></div>
        <div class="card-body">
            <?php if ($stats['pages'] === []): ?>
                <p class="small muted">Belum ada data kunjungan.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Halaman</th><th style="text-align:right">Kunjungan</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['pages'] as $page): ?>
                            <tr>
                                <td><code style="font-size:12.5px"><?= e((string) $page['path']) ?></code></td>
                                <td style="text-align:right"><?= number_format((int) $page['total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Sumber kunjungan (30 hari)</h2></div>
        <div class="card-body">
            <?php if ($stats['referrers'] === []): ?>
                <p class="small muted">Belum ada referrer tercatat.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Referrer</th><th style="text-align:right">Kunjungan</th></tr></thead>
                        <tbody>
                        <?php foreach ($stats['referrers'] as $referrer): ?>
                            <tr>
                                <td><?= e((string) $referrer['referrer']) ?></td>
                                <td style="text-align:right"><?= number_format((int) $referrer['total'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Project paling dilihat</h2></div>
        <div class="card-body">
            <?php if ($popularProjects === []): ?>
                <p class="small muted">Belum ada project terbit.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Project</th><th style="text-align:right">Dilihat</th></tr></thead>
                        <tbody>
                        <?php foreach ($popularProjects as $project): ?>
                            <tr>
                                <td><a href="<?= e(app_url('/project/' . $project['slug'])) ?>" target="_blank" rel="noopener"><?= e((string) $project['title']) ?></a></td>
                                <td style="text-align:right"><?= number_format((int) $project['views'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Artikel paling dilihat</h2></div>
        <div class="card-body">
            <?php if ($popularPosts === []): ?>
                <p class="small muted">Belum ada artikel terbit.</p>
            <?php else: ?>
                <div class="table-wrap">
                    <table class="table">
                        <thead><tr><th>Artikel</th><th style="text-align:right">Dilihat</th></tr></thead>
                        <tbody>
                        <?php foreach ($popularPosts as $post): ?>
                            <tr>
                                <td><a href="<?= e(app_url('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener"><?= e((string) $post['title']) ?></a></td>
                                <td style="text-align:right"><?= number_format((int) $post['views'], 0, ',', '.') ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
