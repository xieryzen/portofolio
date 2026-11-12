<?php
/** @var array<string, mixed> $profile */
/** @var list<array<string, mixed>> $projects */
/** @var list<array<string, mixed>> $featured */
/** @var array<string, mixed> $stats */
/** @var list<array<string, mixed>> $gallery */
/** @var array<string, list<array<string, mixed>>> $skills */
/** @var array<string, mixed>|null $authUser */

use App\View;

$name = (string) ($profile['full_name'] ?? 'Nama Anda');
$avatar = media_url($profile['avatar'] ?? null);
$socials = [
    'github' => 'GitHub',
    'linkedin' => 'LinkedIn',
    'instagram' => 'Instagram',
    'x' => 'X',
    'website' => 'Website',
    'whatsapp' => 'WhatsApp',
];
$activeSocials = array_filter($socials, static fn (string $label, string $key): bool => !empty($profile[$key]), ARRAY_FILTER_USE_BOTH);
$contactEnabled = setting('show_contact', '1') === '1';
$allSkills = [];
foreach ($skills as $group) {
    foreach ($group as $skill) {
        $allSkills[] = $skill;
    }
}
$searchTerm = trim((string) ($searchTerm ?? ''));
$isSearching = $searchTerm !== '';

if ($isSearching) {
    // Saat mencari, semua hasil ditampilkan dalam satu grid.
    $featured = $projects;
    $others = [];
}

$featuredIds = array_map(static fn (array $p): int => (int) $p['id'], $featured);
$others = $isSearching
    ? []
    : array_values(array_filter($projects, static fn (array $p): bool => !in_array((int) $p['id'], $featuredIds, true)));
?>

<!-- ============ HERO ============ -->
<section class="hero">
    <div class="container">
        <div class="hero-grid">
            <div>
                <?php if (!empty($profile['availability'])): ?>
                    <span class="eyebrow"><span class="dot"></span> <?= e((string) $profile['availability']) ?></span>
                <?php else: ?>
                    <span class="eyebrow"><span class="dot"></span> Portofolio</span>
                <?php endif; ?>

                <h1>
                    <?= e($name) ?>
                    <?php if (!empty($profile['headline'])): ?>
                        <span class="grad"><?= e((string) $profile['headline']) ?></span>
                    <?php endif; ?>
                </h1>

                <?php if (!empty($profile['tagline'])): ?>
                    <p class="hero-lead"><?= e((string) $profile['tagline']) ?></p>
                <?php endif; ?>

                <div class="hero-actions">
                    <a class="btn btn-primary" href="#karya">Lihat Karya <?= svg_icon('arrow-right') ?></a>
                    <?php if ($authUser): ?>
                        <a class="btn btn-ghost" href="<?= e(app_url($isAdmin ? '/admin' : '/dashboard')) ?>">
                            <?= svg_icon($isAdmin ? 'dashboard' : 'user') ?> <?= $isAdmin ? 'Panel Admin' : 'Dashboard Saya' ?>
                        </a>
                    <?php elseif ($contactEnabled): ?>
                        <a class="btn btn-ghost" href="#kontak"><?= svg_icon('mail') ?> Hubungi Saya</a>
                    <?php endif; ?>
                </div>

                <?php if ($activeSocials !== []): ?>
                    <div class="social-row">
                        <span class="label">Temukan</span>
                        <?php foreach ($activeSocials as $key => $label): ?>
                            <a class="social-btn" href="<?= e(social_url($key, (string) $profile[$key])) ?>" target="_blank" rel="noopener noreferrer nofollow" aria-label="<?= e($label) ?>" title="<?= e($label) ?>">
                                <?= svg_icon($key) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="hero-visual">
                <div class="orb">
                    <div class="orb-inner">
                        <?php if ($avatar): ?>
                            <img src="<?= e($avatar) ?>" alt="Foto <?= e($name) ?>">
                        <?php else: ?>
                            <?= e(initials($name)) ?>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ((int) ($profile['years_experience'] ?? 0) > 0): ?>
                    <div class="float-card card-a">
                        <span class="fc-icon"><?= svg_icon('sparkles') ?></span>
                        <div>
                            <strong><?= (int) $profile['years_experience'] ?>+ tahun</strong>
                            <span>pengalaman</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if ((int) ($profile['projects_completed'] ?? 0) > 0): ?>
                    <div class="float-card card-b">
                        <span class="fc-icon"><?= svg_icon('folder') ?></span>
                        <div>
                            <strong><?= (int) $profile['projects_completed'] ?> project</strong>
                            <span>terselesaikan</span>
                        </div>
                    </div>
                <?php endif; ?>

                <?php if (!empty($profile['location'])): ?>
                    <div class="float-card card-c">
                        <span class="fc-icon"><?= svg_icon('map-pin') ?></span>
                        <div>
                            <strong><?= e((string) $profile['location']) ?></strong>
                            <span>berbasis di sini</span>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="stats-strip" data-reveal>
            <div class="stat">
                <strong data-count-to="<?= (int) ($profile['years_experience'] ?? 0) ?>">0</strong>
                <span>Tahun Pengalaman</span>
            </div>
            <div class="stat">
                <strong data-count-to="<?= (int) ($profile['projects_completed'] ?? 0) ?>">0</strong>
                <span>Project Selesai</span>
            </div>
            <div class="stat">
                <strong data-count-to="<?= (int) ($profile['clients_count'] ?? 0) ?>">0</strong>
                <span>Klien & Kolaborasi</span>
            </div>
            <div class="stat">
                <strong data-count-to="<?= (int) ($stats['published'] ?? 0) ?>">0</strong>
                <span>Karya Terpublikasi</span>
            </div>
        </div>
    </div>
</section>

<!-- ============ KARYA ============ -->
<section class="section" id="karya">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Portofolio</span>
                <h2 class="section-title"><?= $isSearching ? 'Hasil pencarian' : 'Karya pilihan' ?></h2>
                <p class="section-desc">
                    <?php if ($isSearching): ?>
                        <?= count($projects) ?> project cocok dengan “<?= e($searchTerm) ?>”.
                    <?php else: ?>
                        Kumpulan project yang saya kerjakan — lengkap dengan cerita, peran, dan dokumentasinya.
                    <?php endif; ?>
                </p>
            </div>
            <?php if ($others !== []): ?>
                <a class="link-arrow" href="#semua-karya">Semua project <?= svg_icon('arrow-right') ?></a>
            <?php endif; ?>
        </div>

        <form class="project-search" method="get" action="<?= e(app_url('/')) ?>#karya" role="search">
            <div class="input-group">
                <?= svg_icon('search') ?>
                <input class="input" type="search" name="q" value="<?= e($searchTerm) ?>"
                       placeholder="Cari project, teknologi, atau kategori…" maxlength="80" aria-label="Cari project">
            </div>
            <button class="btn btn-primary" type="submit">Cari</button>
            <?php if ($isSearching): ?>
                <a class="btn btn-ghost" href="<?= e(app_url('/')) ?>#karya">Reset</a>
            <?php endif; ?>
        </form>

        <?php if ($projects === []): ?>
            <div class="card">
                <div class="empty-state">
                    <?= svg_icon($isSearching ? 'search' : 'folder') ?>
                    <h3><?= $isSearching ? 'Tidak ada project yang cocok' : 'Belum ada project' ?></h3>
                    <p>
                        <?php if ($isSearching): ?>
                            Coba kata kunci lain, misalnya nama teknologi atau kategori.
                        <?php else: ?>
                            Project yang ditambahkan dari panel admin akan tampil di sini.
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        <?php else: ?>
            <div class="project-grid featured-grid">
                <?php foreach ($featured as $project): ?>
                    <?= View::partial('partials/project-card', ['project' => $project]) ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php if ($others !== []): ?>
<section class="section-sm" id="semua-karya">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Arsip</span>
                <h2 class="section-title" style="font-size:clamp(1.5rem,2.6vw,2.1rem)">Project lainnya</h2>
            </div>
        </div>
        <div class="project-grid">
            <?php foreach ($others as $project): ?>
                <?= View::partial('partials/project-card', ['project' => $project]) ?>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ TENTANG ============ -->
<section class="section" id="tentang">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Tentang</span>
                <h2 class="section-title">Di balik karya-karya ini</h2>
            </div>
        </div>

        <div class="about-grid">
            <div class="about-text" data-reveal>
                <?php
                $bio = (string) ($profile['bio'] ?? '');
                $paragraphs = array_filter(array_map('trim', preg_split('~\n\s*\n~', $bio) ?: []));
                ?>
                <?php if ($paragraphs !== []): ?>
                    <?php foreach ($paragraphs as $paragraph): ?>
                        <p><?= nl2br(e($paragraph)) ?></p>
                    <?php endforeach; ?>
                <?php else: ?>
                    <p class="muted">Deskripsi profil belum diisi. Tambahkan dari halaman <strong>Admin → Profil</strong>.</p>
                <?php endif; ?>

                <?php if ($allSkills !== []): ?>
                    <div class="chips">
                        <?php foreach ($allSkills as $skill): ?>
                            <span class="chip"><?= e((string) $skill['name']) ?></span>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>

            <div class="about-side" data-reveal>
                <?php $coverImage = media_url($profile['cover'] ?? null); ?>
                <?php if ($coverImage): ?>
                    <figure class="about-cover">
                        <img src="<?= e($coverImage) ?>" alt="Foto sampul <?= e($name) ?>" loading="lazy">
                    </figure>
                <?php endif; ?>

            <div class="info-card">
                <h3>Informasi singkat</h3>
                <div class="info-list">
                    <?php if (!empty($profile['location'])): ?>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('map-pin') ?></span>
                            <div><small>Lokasi</small><span><?= e((string) $profile['location']) ?></span></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['public_email'])): ?>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('mail') ?></span>
                            <div><small>Email</small><a href="mailto:<?= e((string) $profile['public_email']) ?>"><?= e((string) $profile['public_email']) ?></a></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['phone'])): ?>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('phone') ?></span>
                            <div><small>Telepon</small><span><?= e((string) $profile['phone']) ?></span></div>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($profile['availability'])): ?>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('briefcase') ?></span>
                            <div><small>Status</small><span><?= e((string) $profile['availability']) ?></span></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            </div>
        </div>

        <?php if ($skills !== []): ?>
            <div class="skills-wrap">
                <?php foreach ($skills as $category => $items): ?>
                    <div class="skill-group" data-reveal>
                        <h4><?= e((string) $category) ?></h4>
                        <?php foreach ($items as $skill): ?>
                            <div class="skill-item">
                                <div class="skill-top">
                                    <span><?= e((string) $skill['name']) ?></span>
                                    <span><?= (int) $skill['level'] ?>%</span>
                                </div>
                                <div class="skill-bar"><i style="--level: <?= (int) $skill['level'] ?>%"></i></div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- ============ DOKUMENTASI ============ -->
<?php if ($gallery !== []): ?>
<section class="section" id="dokumentasi">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Dokumentasi</span>
                <h2 class="section-title">Sekilas proses & hasil</h2>
                <p class="section-desc">Tangkapan layar dan foto dokumentasi dari project yang sedang maupun sudah dikerjakan.</p>
            </div>
        </div>

        <div class="doc-grid">
            <?php foreach ($gallery as $image): ?>
                <button type="button" class="doc-item" data-reveal
                    data-lightbox="<?= e(media_url((string) $image['file_name']) ?? '') ?>"
                    data-caption="<?= e((string) ($image['caption'] ?: $image['project_title'])) ?>">
                    <img src="<?= e(media_url((string) $image['file_name']) ?? '') ?>" alt="<?= e((string) ($image['caption'] ?: $image['project_title'])) ?>" loading="lazy">
                    <figcaption><?= e((string) ($image['caption'] ?: $image['project_title'])) ?></figcaption>
                </button>
            <?php endforeach; ?>
        </div>
    </div>
</section>
<?php endif; ?>

<!-- ============ KONTAK ============ -->
<?php if ($contactEnabled): ?>
<section class="section" id="kontak">
    <div class="container">
        <div class="section-head">
            <div>
                <span class="section-label">Kontak</span>
                <h2 class="section-title">Mari bekerja sama</h2>
                <p class="section-desc">Punya ide, project, atau sekadar ingin berdiskusi? Kirimkan pesan — saya akan membalas secepatnya.</p>
            </div>
        </div>

        <div class="contact-grid">
            <div class="contact-panel" data-reveal>
                <div>
                    <h3><?= e($name) ?></h3>
                    <p class="mt-1"><?= e((string) ($profile['availability'] ?: 'Terbuka untuk kolaborasi baru.')) ?></p>
                </div>

                <div class="contact-list">
                    <?php if (!empty($profile['public_email'])): ?>
                        <a href="mailto:<?= e((string) $profile['public_email']) ?>"><?= svg_icon('mail') ?> <?= e((string) $profile['public_email']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($profile['phone'])): ?>
                        <a href="tel:<?= e(preg_replace('~[^0-9+]~', '', (string) $profile['phone']) ?? '') ?>"><?= svg_icon('phone') ?> <?= e((string) $profile['phone']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($profile['location'])): ?>
                        <div><?= svg_icon('map-pin') ?> <?= e((string) $profile['location']) ?></div>
                    <?php endif; ?>

                    <?php foreach ($activeSocials as $key => $label): ?>
                        <a href="<?= e(social_url($key, (string) $profile[$key])) ?>" target="_blank" rel="noopener noreferrer nofollow">
                            <?= svg_icon($key) ?> <?= e($label) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="form-card" data-reveal>
                <form method="post" action="<?= e(app_url('/contact')) ?>" class="form-grid" novalidate>
                    <?= csrf_field() ?>
                    <input type="text" name="website" value="" tabindex="-1" autocomplete="off" class="hidden" aria-hidden="true">

                    <div class="field">
                        <label for="contact-name">Nama</label>
                        <input class="input <?= error_for('name') ? 'has-error' : '' ?>" type="text" id="contact-name" name="name" value="<?= e(old('name')) ?>" placeholder="Nama Anda" required maxlength="120">
                        <?php if ($message = error_for('name')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>

                    <div class="field">
                        <label for="contact-email">Email</label>
                        <input class="input <?= error_for('email') ? 'has-error' : '' ?>" type="email" id="contact-email" name="email" value="<?= e(old('email')) ?>" placeholder="nama@email.com" required maxlength="190">
                        <?php if ($message = error_for('email')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>

                    <div class="field full">
                        <label for="contact-subject">Subjek <span class="hint">(opsional)</span></label>
                        <input class="input" type="text" id="contact-subject" name="subject" value="<?= e(old('subject')) ?>" placeholder="Tentang apa pesan ini?" maxlength="200">
                    </div>

                    <div class="field full">
                        <label for="contact-body">Pesan</label>
                        <textarea class="textarea <?= error_for('body') ? 'has-error' : '' ?>" id="contact-body" name="body" rows="5" placeholder="Ceritakan kebutuhan atau ide Anda…" required maxlength="5000"><?= e(old('body')) ?></textarea>
                        <?php if ($message = error_for('body')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>

                    <div class="full flex between flex-wrap">
                        <span class="small muted">Data Anda hanya dipakai untuk membalas pesan ini.</span>
                        <button class="btn btn-primary" type="submit">Kirim Pesan <?= svg_icon('arrow-right') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>
<?php endif; ?>
