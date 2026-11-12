<?php
/** @var array{daily: list<array{date: string, total: int}>, totals: array{today: int, week: int, month: int, all: int}, pages: list<array{path: string, total: int}>, referrers: list<array{referrer: string, total: int}>} $stats */

$max = 0;
foreach ($stats['daily'] as $day) {
    $max = max($max, $day['total']);
}
$max = max(1, $max);
$label = static function (string $date): string {
    $months = ['', 'Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
    $time = strtotime($date) ?: time();

    return date('j', $time) . ' ' . ($months[(int) date('n', $time)] ?? '');
};
?>
<div class="admin-head">
    <div>
        <span class="section-label">Sistem</span>
        <h1 class="mt-1">Statistik kunjungan</h1>
        <p>Hanya halaman publik yang dihitung. Bot dan crawler difilter, tidak ada IP atau cookie yang disimpan.</p>
    </div>
    <span class="badge"><?= count($stats['daily']) ?> hari terakhir</span>
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
    <div class="card-head"><h2>Kunjungan 14 hari terakhir</h2></div>
    <div class="card-body">
        <div class="visit-chart" role="img" aria-label="Grafik kunjungan harian 14 hari terakhir">
            <?php foreach ($stats['daily'] as $day): ?>
                <?php $height = (int) max(4, round(($day['total'] / $max) * 120)); ?>
                <div class="visit-col" title="<?= e($label($day['date'])) ?>: <?= (int) $day['total'] ?> kunjungan">
                    <span class="visit-value"><?= (int) $day['total'] ?></span>
                    <span class="visit-bar" style="height:<?= $height ?>px"></span>
                    <span class="visit-date"><?= e($label($day['date'])) ?></span>
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
</div>
