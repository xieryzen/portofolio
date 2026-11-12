<?php
/** @var array<string, mixed> $profile */
/** @var array<string, mixed>|null $authUser */

$avatar = media_url($profile['avatar'] ?? null);
$cover = media_url($profile['cover'] ?? null);
$value = static fn (string $key): string => \old($key, $profile[$key] ?? '');
?>

<div class="admin-head">
    <div>
        <span class="section-label">Profil</span>
        <h1 class="mt-1">Profil portofolio</h1>
        <p>Informasi ini tampil di halaman utama: nama, headline, deskripsi, foto, dan kontak.</p>
    </div>
    <div class="actions">
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/')) ?>" target="_blank" rel="noopener"><?= svg_icon('eye', 'icon-sm') ?> Pratinjau</a>
    </div>
</div>

<div class="grid-main-side">
<form method="post" action="<?= e(app_url('/admin/profile')) ?>" style="display:grid;gap:20px" novalidate>
    <?= csrf_field() ?>

    <div style="display:grid;gap:20px">
        <div class="card">
            <div class="card-head"><h2>Identitas utama</h2></div>
            <div class="card-body form-section">
                <div class="form-row">
                    <div class="field">
                        <label for="full_name">Nama lengkap <span class="hint">*</span></label>
                        <input class="input <?= error_for('full_name') ? 'has-error' : '' ?>" type="text" id="full_name" name="full_name" value="<?= e($value('full_name')) ?>" required maxlength="150">
                        <?php if ($message = error_for('full_name')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="headline">Headline / profesi</label>
                        <input class="input" type="text" id="headline" name="headline" value="<?= e($value('headline')) ?>" placeholder="Full-Stack Developer" maxlength="200">
                        <span class="hint">Tampil besar di bawah nama.</span>
                    </div>
                    <div class="field full">
                        <label for="tagline">Tagline singkat</label>
                        <input class="input" type="text" id="tagline" name="tagline" value="<?= e($value('tagline')) ?>" placeholder="Satu kalimat tentang Anda" maxlength="300">
                    </div>
                    <div class="field full">
                        <label for="bio">Deskripsi / tentang saya</label>
                        <textarea class="textarea" id="bio" name="bio" rows="9" maxlength="8000" placeholder="Ceritakan pengalaman, fokus, dan pendekatan kerja Anda…"><?= e($value('bio')) ?></textarea>
                        <span class="hint">Pisahkan paragraf dengan baris kosong. Mendukung teks biasa.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Statistik & status</h2></div>
            <div class="card-body form-section">
                <div class="form-row three">
                    <div class="field">
                        <label for="years_experience">Tahun pengalaman</label>
                        <input class="input" type="number" id="years_experience" name="years_experience" value="<?= e($value('years_experience')) ?>" min="0" max="80">
                    </div>
                    <div class="field">
                        <label for="projects_completed">Project selesai</label>
                        <input class="input" type="number" id="projects_completed" name="projects_completed" value="<?= e($value('projects_completed')) ?>" min="0" max="100000">
                    </div>
                    <div class="field">
                        <label for="clients_count">Klien</label>
                        <input class="input" type="number" id="clients_count" name="clients_count" value="<?= e($value('clients_count')) ?>" min="0" max="100000">
                    </div>
                    <div class="field">
                        <label for="availability">Status ketersediaan</label>
                        <input class="input" type="text" id="availability" name="availability" value="<?= e($value('availability')) ?>" placeholder="Terbuka untuk freelance" maxlength="120">
                    </div>
                    <div class="field">
                        <label for="location">Lokasi</label>
                        <input class="input" type="text" id="location" name="location" value="<?= e($value('location')) ?>" placeholder="Pekanbaru, Indonesia" maxlength="150">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Kontak & tautan</h2></div>
            <div class="card-body form-section">
                <div class="form-row">
                    <div class="field">
                        <label for="public_email">Email publik</label>
                        <input class="input <?= error_for('public_email') ? 'has-error' : '' ?>" type="email" id="public_email" name="public_email" value="<?= e($value('public_email')) ?>" maxlength="190">
                        <?php if ($message = error_for('public_email')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="phone">Telepon / WhatsApp</label>
                        <input class="input" type="text" id="phone" name="phone" value="<?= e($value('phone')) ?>" placeholder="+62 812 3456 7890" maxlength="60">
                    </div>
                    <div class="field">
                        <label for="github">GitHub</label>
                        <input class="input <?= error_for('github') ? 'has-error' : '' ?>" type="url" id="github" name="github" value="<?= e($value('github')) ?>" placeholder="https://github.com/username" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="linkedin">LinkedIn</label>
                        <input class="input" type="url" id="linkedin" name="linkedin" value="<?= e($value('linkedin')) ?>" placeholder="https://linkedin.com/in/username" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="instagram">Instagram</label>
                        <input class="input" type="url" id="instagram" name="instagram" value="<?= e($value('instagram')) ?>" placeholder="https://instagram.com/username" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="x">X / Twitter</label>
                        <input class="input" type="url" id="x" name="x" value="<?= e($value('x')) ?>" placeholder="https://x.com/username" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="website">Website</label>
                        <input class="input" type="url" id="website" name="website" value="<?= e($value('website')) ?>" placeholder="https://situs-anda.com" maxlength="200">
                    </div>
                    <div class="field">
                        <label for="whatsapp">Nomor WhatsApp</label>
                        <input class="input" type="text" id="whatsapp" name="whatsapp" value="<?= e($value('whatsapp')) ?>" placeholder="6281234567890" maxlength="60">
                        <span class="hint">Kosongkan jika tidak ingin menampilkan tautan WhatsApp.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= svg_icon('check', 'icon-sm') ?> Simpan perubahan</button>
            <a class="btn btn-ghost" href="<?= e(app_url('/admin')) ?>">Batal</a>
        </div>
    </div>
</form>

    <div style="display:grid;gap:20px">
        <div class="card">
            <div class="card-head"><h2>Foto profil</h2></div>
            <div class="card-body">
                <div class="upload-preview avatar" id="avatar-preview" data-preview-target="avatar-preview" style="margin-inline:auto">
                    <?php if ($avatar): ?>
                        <img src="<?= e($avatar) ?>" alt="Foto profil">
                    <?php else: ?>
                        <span><?= svg_icon('user') ?>Belum ada foto</span>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= e(app_url('/admin/profile/image')) ?>" enctype="multipart/form-data" class="mt-2" style="display:grid;gap:10px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="avatar">
                    <div class="field">
                        <label for="avatar-file" class="hint">Pilih gambar profil</label>
                        <input class="input" type="file" id="avatar-file" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required style="padding:9px" data-preview-input="#avatar-preview">
                    </div>
                    <button class="btn btn-soft btn-sm btn-block" type="submit"><?= svg_icon('image', 'icon-sm') ?> Unggah foto profil</button>
                    <span class="hint text-center">JPG, PNG, WEBP, GIF · maks <?= \App\Uploader::maxUploadMb() ?> MB</span>
                </form>

                <?php if ($avatar): ?>
                    <form method="post" action="<?= e(app_url('/admin/profile/image/remove')) ?>" class="mt-1" data-confirm="Hapus foto profil? Tampilan akan kembali memakai inisial nama.">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="avatar">
                        <button class="btn btn-danger btn-sm btn-block" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus foto</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Foto sampul</h2></div>
            <div class="card-body">
                <div class="upload-preview cover" id="cover-preview" data-preview-target="cover-preview">
                    <?php if ($cover): ?>
                        <img src="<?= e($cover) ?>" alt="Foto sampul">
                    <?php else: ?>
                        <span><?= svg_icon('image') ?>Belum ada sampul</span>
                    <?php endif; ?>
                </div>

                <form method="post" action="<?= e(app_url('/admin/profile/image')) ?>" enctype="multipart/form-data" class="mt-2" style="display:grid;gap:10px">
                    <?= csrf_field() ?>
                    <input type="hidden" name="type" value="cover">
                    <div class="field">
                        <label for="cover-file-input" class="hint">Pilih gambar sampul</label>
                        <input class="input" type="file" id="cover-file-input" name="image" accept="image/jpeg,image/png,image/webp,image/gif" required style="padding:9px" data-preview-input="#cover-preview">
                    </div>
                    <button class="btn btn-soft btn-sm btn-block" type="submit"><?= svg_icon('image', 'icon-sm') ?> Unggah sampul</button>
                </form>

                <?php if ($cover): ?>
                    <form method="post" action="<?= e(app_url('/admin/profile/image/remove')) ?>" class="mt-1" data-confirm="Hapus foto sampul?">
                        <?= csrf_field() ?>
                        <input type="hidden" name="type" value="cover">
                        <button class="btn btn-danger btn-sm btn-block" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus sampul</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>CV (PDF)</h2></div>
            <div class="card-body">
                <?php $cvFile = setting('cv_file', ''); ?>
                <?php if ($cvFile !== ''): ?>
                    <p class="small" style="margin-bottom:12px">
                        <?= svg_icon('file', 'icon-sm') ?>
                        <a href="<?= e(app_url('/cv')) ?>" target="_blank" rel="noopener">Pratinjau CV</a>
                        <span class="muted">· <?= e((string) setting('cv_original_name', 'cv.pdf')) ?></span>
                    </p>
                <?php else: ?>
                    <p class="small muted" style="margin-bottom:12px">
                        Unggah CV berformat PDF. Tombol <strong>Unduh CV</strong> akan muncul di halaman publik
                        (hero, navbar, dan area lain yang relevan).
                    </p>
                <?php endif; ?>

                <form method="post" action="<?= e(app_url('/admin/profile/cv')) ?>" enctype="multipart/form-data" style="display:grid;gap:10px">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="cv-file" class="hint"><?= $cvFile !== '' ? 'Ganti berkas CV' : 'Pilih berkas CV' ?></label>
                        <input class="input" type="file" id="cv-file" name="cv" accept="application/pdf,.pdf" required style="padding:9px">
                    </div>
                    <button class="btn btn-soft btn-sm btn-block" type="submit"><?= svg_icon('download', 'icon-sm') ?> <?= $cvFile !== '' ? 'Ganti CV' : 'Unggah CV' ?></button>
                    <span class="hint text-center">PDF · maks <?= \App\Uploader::maxCvMb() ?> MB</span>
                </form>

                <?php if ($cvFile !== ''): ?>
                    <form method="post" action="<?= e(app_url('/admin/profile/cv/delete')) ?>" class="mt-1" data-confirm="Hapus CV? Tombol Unduh CV akan disembunyikan.">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm btn-block" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus CV</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Tips</h2></div>
            <div class="card-body">
                <ul style="display:grid;gap:10px;font-size:13px;color:var(--muted)">
                    <li>Gunakan foto profil persegi minimal 500×500 px.</li>
                    <li>Foto sampul ideal 1600×900 px.</li>
                    <li>Gambar JPG/PNG otomatis dikonversi ke WebP (lebih ringan) bila server punya ekstensi GD.</li>
                    <li>Headline yang jelas membantu pengunjung memahami fokus Anda.</li>
                    <li>Perubahan langsung tampil setelah disimpan.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
