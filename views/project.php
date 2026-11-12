<?php
/** @var array<string, mixed> $project */
/** @var list<array<string, mixed>> $images */
/** @var list<array<string, mixed>> $related */
/** @var array<string, mixed> $profile */

use App\View;

$cover = media_url($project['cover'] ?? null);
$tags = array_filter(array_map('trim', explode(',', (string) ($project['tech_stack'] ?? ''))));
$paragraphs = array_filter(array_map('trim', preg_split('~\n\s*\n~', (string) ($project['description'] ?? '')) ?: []));
$meta = array_filter([
    'Kategori' => (string) ($project['category'] ?? ''),
    'Klien' => (string) ($project['client'] ?? ''),
    'Peran' => (string) ($project['role'] ?? ''),
    'Tahun' => (string) ($project['year'] ?? ''),
], static fn (string $value): bool => $value !== '');
?>

<section class="page-hero">
    <div class="container">
        <a class="back-link" href="<?= e(app_url('/#karya')) ?>"><?= svg_icon('arrow-left') ?> Kembali ke portofolio</a>

        <span class="eyebrow"><span class="dot"></span> <?= e((string) ($project['category'] ?: 'Project')) ?></span>
        <h1 class="mt-2"><?= e((string) $project['title']) ?></h1>
        <?php if (!empty($project['summary'])): ?>
            <p class="lead"><?= e((string) $project['summary']) ?></p>
        <?php endif; ?>

        <?php if ($meta !== []): ?>
            <div class="detail-meta" data-reveal>
                <?php foreach ($meta as $label => $value): ?>
                    <div>
                        <small><?= e($label) ?></small>
                        <span><?= e($value) ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="detail-cover">
            <?php if ($cover): ?>
                <img src="<?= e($cover) ?>" alt="Cover <?= e((string) $project['title']) ?>">
            <?php else: ?>
                <div class="cover-fallback"><?= e(initials((string) $project['title'])) ?></div>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section-sm">
    <div class="container">
        <div class="detail-body">
            <div class="prose">
                <span class="section-label">Tentang project</span>
                <h2 class="section-title" style="font-size:clamp(1.4rem,2.4vw,1.9rem);margin-bottom:20px">Ringkasan pekerjaan</h2>

                <?php if ($paragraphs !== []): ?>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p><?= nl2br(e($paragraph)) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">Deskripsi project belum ditambahkan.</p>
                <?php endif; ?>

                <?php if ($images !== []): ?>
                    <div class="mt-3">
                        <span class="section-label">Dokumentasi</span>
                        <h2 class="section-title" style="font-size:clamp(1.4rem,2.4vw,1.9rem);margin:12px 0 22px">Galeri project</h2>
                        <div class="doc-grid">
                            <?php foreach ($images as $image): ?>
                                <button type="button" class="doc-item"
                                    data-lightbox="<?= e(media_url((string) $image['file_name']) ?? '') ?>"
                                    data-caption="<?= e((string) ($image['caption'] ?: $project['title'])) ?>">
                                    <img src="<?= e(media_url((string) $image['file_name']) ?? '') ?>" alt="<?= e((string) ($image['caption'] ?: $project['title'])) ?>" loading="lazy">
                                    <?php if (!empty($image['caption'])): ?>
                                        <figcaption><?= e((string) $image['caption']) ?></figcaption>
                                    <?php endif; ?>
                                </button>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>
            </div>

            <aside class="detail-side">
                <?php if ($tags !== []): ?>
                    <div class="side-card">
                        <h4>Tech stack</h4>
                        <div class="tag-list">
                            <?php foreach ($tags as $tag): ?>
                                <span class="tag"><?= e($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($project['project_url']) || !empty($project['repo_url'])): ?>
                    <div class="side-card">
                        <h4>Tautan</h4>
                        <div class="side-actions">
                            <?php if (!empty($project['project_url'])): ?>
                                <a class="btn btn-primary btn-sm" href="<?= e((string) $project['project_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= svg_icon('external') ?> Kunjungi project
                                </a>
                            <?php endif; ?>
                            <?php if (!empty($project['repo_url'])): ?>
                                <a class="btn btn-ghost btn-sm" href="<?= e((string) $project['repo_url']) ?>" target="_blank" rel="noopener noreferrer">
                                    <?= svg_icon('github') ?> Lihat repository
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="side-card">
                    <h4>Butuh hal serupa?</h4>
                    <p class="small muted" style="margin-bottom:14px">
                        Saya terbuka untuk diskusi project baru. Ceritakan kebutuhan Anda.
                    </p>
                    <a class="btn btn-soft btn-sm btn-block" href="<?= e(app_url('/#kontak')) ?>"><?= svg_icon('mail') ?> Hubungi saya</a>
                </div>
            </aside>
        </div>
    </div>
</section>

<?php if ($related !== []): ?>
<section class="section-sm" style="border-top:1px solid var(--line)">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Lainnya</span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">Project lain</h2>
            </div>
            <a class="link-arrow" href="<?= e(app_url('/#karya')) ?>">Semua karya <?= svg_icon('arrow-right') ?></a>
        </div>
        <div class="project-grid">
            <?php foreach ($related as $item): ?>
                <?= View::partial('partials/project-card', ['project' => $item]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>
