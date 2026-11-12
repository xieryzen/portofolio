<?php
/** @var array<string, mixed> $profile */
/** @var array<string, mixed> $user */
/** @var list<array<string, mixed>> $projects */
/** @var list<array<string, mixed>> $gallery */
/** @var array<string, mixed> $stats */
/** @var array<string, list<array<string, mixed>>> $skills */

use App\View;

$avatar = media_url($profile['avatar'] ?? null);
$firstName = explode(' ', (string) ($user['name'] ?? 'Pengguna'))[0];
?>

<section class="section-sm" style="padding-top:44px">
    <div class="container">
        <div class="user-hero" data-reveal>
            <div class="avatar">
                <?php if (!empty($user['name'])): ?>
                    <?= e(initials((string) $user['name'])) ?>
                <?php endif; ?>
            </div>
            <div>
                <span class="section-label">Area Anggota</span>
                <h1 class="mt-1">Halo, <?= e($firstName) ?> 👋</h1>
                <p>Anda masuk sebagai <strong style="color:var(--text)"><?= e((string) $user['email']) ?></strong>. Berikut portofolio <?= e((string) ($profile['full_name'] ?? '')) ?>.</p>
            </div>
            <div class="actions">
                <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/profil')) ?>"><?= svg_icon('user') ?> Akun Saya</a>
                <?php if ($isAdmin): ?>
                    <a class="btn btn-primary btn-sm" href="<?= e(app_url('/admin')) ?>"><?= svg_icon('dashboard') ?> Panel Admin</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats-strip" style="margin-top:22px">
            <div class="stat"><strong><?= (int) ($stats['published'] ?? 0) ?></strong><span>Karya</span></div>
            <div class="stat"><strong><?= (int) ($profile['years_experience'] ?? 0) ?>+</strong><span>Tahun Pengalaman</span></div>
            <div class="stat"><strong><?= (int) ($profile['projects_completed'] ?? 0) ?></strong><span>Project Selesai</span></div>
            <div class="stat"><strong><?= (int) ($profile['clients_count'] ?? 0) ?></strong><span>Klien</span></div>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Portofolio</span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">Karya terbaru</h2>
            </div>
            <a class="link-arrow" href="<?= e(app_url('/')) ?>">Halaman utama <?= svg_icon('arrow-right') ?></a>
        </div>

        <?php if ($projects === []): ?>
            <div class="card">
                <div class="empty-state">
                    <?= svg_icon('folder') ?>
                    <h3>Belum ada karya yang dipublikasikan</h3>
                    <p>Karya akan muncul setelah owner menambahkan project dari panel admin.</p>
                </div>
            </div>
        <?php else: ?>
            <div class="project-grid">
                <?php foreach (array_slice($projects, 0, 6) as $project): ?>
                    <?= View::partial('partials/project-card', ['project' => $project]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if (!empty($skills)): ?>
<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Keahlian</span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">Yang dikuasai</h2>
            </div>
        </div>
        <div class="chips">
            <?php foreach ($skills as $group): ?>
                <?php foreach ($group as $skill): ?>
                    <span class="chip"><?= e((string) $skill['name']) ?></span>
                <?php endforeach; ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<?php if (!empty($gallery)): ?>
<section class="section-sm">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Dokumentasi</span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">Galeri</h2>
            </div>
        </div>
        <div class="doc-grid">
            <?php foreach ($gallery as $image): ?>
                <button type="button" class="doc-item"
                    data-lightbox="<?= e(media_url((string) $image['file_name']) ?? '') ?>"
                    data-caption="<?= e((string) ($image['caption'] ?: $image['project_title'])) ?>">
                    <img src="<?= e(media_url((string) $image['file_name']) ?? '') ?>" alt="<?= e((string) $image['project_title']) ?>" loading="lazy">
                    <figcaption><?= e((string) ($image['caption'] ?: $image['project_title'])) ?></figcaption>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
