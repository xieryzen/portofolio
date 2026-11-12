<?php
/** @var int $databaseSize */
/** @var string $databasePath */
/** @var string $driver */
/** @var bool $isSqlite */
/** @var array{files: int, bytes: int} $storage */
/** @var int $maxMb */
/** @var list<array{name: string, size: int, time: int}> $backups */
?>
<div class="admin-head">
    <div>
        <span class="section-label">Sistem</span>
        <h1 class="mt-1">Cadangan data</h1>
        <p>Unduh salinan database dan berkas unggahan, atau pulihkan dari cadangan sebelumnya. Khusus owner.</p>
    </div>
</div>

<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-head"><h2>Ringkasan data</h2></div>
        <div class="card-body">
            <div class="detail-meta" style="grid-template-columns:repeat(2,minmax(0,1fr))">
                <div>
                    <span>Database</span>
                    <strong><?= e(format_bytes($databaseSize)) ?></strong>
                    <small><?= e(strtoupper($driver)) ?></small>
                </div>
                <div>
                    <span>Berkas unggahan</span>
                    <strong><?= e(format_bytes((int) $storage['bytes'])) ?></strong>
                    <small><?= (int) $storage['files'] ?> berkas (upload + rilis)</small>
                </div>
            </div>

            <div class="flex" style="gap:10px;flex-wrap:wrap;margin-top:18px">
                <?php if ($isSqlite): ?>
                    <a class="btn btn-primary" href="<?= e(app_url('/admin/backup/database')) ?>">
                        <?= svg_icon('database', 'icon-sm') ?> Unduh database
                    </a>
                <?php else: ?>
                    <span class="badge">MySQL — gunakan <code>mysqldump</code> untuk backup database</span>
                <?php endif; ?>
                <a class="btn btn-soft" href="<?= e(app_url('/admin/backup/archive')) ?>">
                    <?= svg_icon('package', 'icon-sm') ?> Unduh semua (ZIP)
                </a>
            </div>

            <p class="hint" style="margin-top:14px">
                ZIP berisi <code>database.sqlite</code>, folder <code>uploads/</code>, dan <code>releases/</code> —
                bisa dipulihkan di server lain lewat formulir di samping.
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2>Pulihkan dari cadangan</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(app_url('/admin/backup/restore')) ?>" enctype="multipart/form-data" class="form-section">
                <?= csrf_field() ?>

                <div class="field">
                    <label for="backup">Berkas ZIP cadangan <span class="hint">*</span></label>
                    <input class="input" type="file" id="backup" name="backup" accept=".zip,application/zip" required>
                    <span class="hint">Maksimal <?= (int) $maxMb ?> MB. Gunakan ZIP yang diunduh dari halaman ini.</span>
                </div>

                <label class="check-row" style="display:flex;gap:10px;align-items:flex-start">
                    <input type="checkbox" name="confirm" value="1" required style="margin-top:4px">
                    <span>Saya mengerti data saat ini akan diganti dengan isi cadangan. Salinan database sebelum restore otomatis disimpan.</span>
                </label>

                <button class="btn btn-danger" type="submit">
                    <?= svg_icon('upload', 'icon-sm') ?> Pulihkan sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top:20px">
    <div class="card-head">
        <h2>Cadangan otomatis sebelum restore</h2>
        <span class="badge"><?= count($backups) ?></span>
    </div>
    <div class="card-body">
        <?php if ($backups === []): ?>
            <div class="empty-state" style="padding:26px 10px">
                <?= svg_icon('clock') ?>
                <h3>Belum ada cadangan otomatis</h3>
                <p>Salinan dibuat otomatis setiap kali kamu melakukan restore.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Berkas</th><th>Ukuran</th><th>Waktu</th><th></th></tr>
                    </thead>
                    <tbody>
                    <?php foreach ($backups as $backup): ?>
                        <tr>
                            <td><span class="row-title"><strong><?= e($backup['name']) ?></strong></span></td>
                            <td><?= e(format_bytes((int) $backup['size'])) ?></td>
                            <td><?= e(date('d M Y H:i', (int) $backup['time'])) ?></td>
                            <td class="actions">
                                <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/admin/backup/file/' . rawurlencode($backup['name']))) ?>">
                                    <?= svg_icon('download', 'icon-sm') ?> Unduh
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>
