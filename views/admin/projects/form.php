<?php
/** @var array<string, mixed>|null $project */
/** @var list<array<string, mixed>> $images */

$isEdit = $project !== null;
$action = $isEdit ? '/admin/projects/' . $project['id'] : '/admin/projects';
$cover = $isEdit ? media_url($project['cover'] ?? null) : null;
$value = static fn (string $key, string $default = ''): string => (string) \old($key, $project[$key] ?? $default);
$selected = static fn (string $key, string $expected): string => $value($key) === $expected ? 'selected' : '';
$categories = ['Web App', 'Website', 'Dashboard', 'Mobile App', 'API / Backend', 'Desain UI/UX', 'Lainnya'];
?>

<div class="admin-head">
    <div>
        <span class="section-label"><?= $isEdit ? 'Edit project' : 'Project baru' ?></span>
        <h1 class="mt-1"><?= $isEdit ? e((string) $project['title']) : 'Tambah project' ?></h1>
        <p><?= $isEdit ? 'Perbarui informasi dan dokumentasi project ini.' : 'Isi informasi project yang akan tampil di portofolio.' ?></p>
    </div>
    <div class="actions">
        <?php if ($isEdit): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/project/' . $project['slug'])) ?>" target="_blank" rel="noopener"><?= svg_icon('eye', 'icon-sm') ?> Pratinjau</a>
        <?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/admin/projects')) ?>"><?= svg_icon('arrow-left', 'icon-sm') ?> Kembali</a>
    </div>
</div>

<form method="post" action="<?= e(app_url($action)) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="grid-main-side">
        <div style="display:grid;gap:20px">
            <div class="card">
                <div class="card-head"><h2>Informasi utama</h2></div>
                <div class="card-body form-section">
                    <div class="field">
                        <label for="title">Judul project <span class="hint">*</span></label>
                        <input class="input <?= error_for('title') ? 'has-error' : '' ?>" type="text" id="title" name="title" value="<?= e($value('title')) ?>" placeholder="Contoh: Sistem Kasir Toko Online" required maxlength="180">
                        <span class="hint">Slug URL dibuat otomatis dari judul.</span>
                    </div>

                    <div class="field">
                        <label for="summary">Ringkasan</label>
                        <input class="input" type="text" id="summary" name="summary" value="<?= e($value('summary')) ?>" placeholder="Satu kalimat yang menjelaskan project" maxlength="300">
                    </div>

                    <div class="field">
                        <label for="description">Deskripsi lengkap</label>
                        <textarea class="textarea" id="description" name="description" rows="14" maxlength="20000" placeholder="Ceritakan latar belakang, tantangan, solusi, dan hasil project…"><?= e($value('description')) ?></textarea>
                        <span class="hint">Pisahkan paragraf dengan baris kosong. Teks biasa, aman dari HTML.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Detail project</h2></div>
                <div class="card-body form-section">
                    <div class="form-row">
                        <div class="field">
                            <label for="category">Kategori</label>
                            <input class="input" type="text" id="category" name="category" value="<?= e($value('category', 'Web App')) ?>" list="category-options" maxlength="80">
                            <datalist id="category-options">
                                <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"></option><?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="field">
                            <label for="client">Klien</label>
                            <input class="input" type="text" id="client" name="client" value="<?= e($value('client')) ?>" maxlength="150" placeholder="Nama klien / personal">
                        </div>
                        <div class="field">
                            <label for="role">Peran Anda</label>
                            <input class="input" type="text" id="role" name="role" value="<?= e($value('role')) ?>" maxlength="150" placeholder="Full-Stack Developer">
                        </div>
                        <div class="field">
                            <label for="year">Tahun</label>
                            <input class="input" type="text" id="year" name="year" value="<?= e($value('year')) ?>" maxlength="10" placeholder="<?= date('Y') ?>">
                        </div>
                        <div class="field full">
                            <label for="tech_stack">Tech stack</label>
                            <input class="input" type="text" id="tech_stack" name="tech_stack" value="<?= e($value('tech_stack')) ?>" maxlength="300" placeholder="PHP 8.3, MySQL, Alpine.js">
                            <span class="hint">Pisahkan dengan koma.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Tautan</h2></div>
                <div class="card-body form-section">
                    <div class="form-row">
                        <div class="field">
                            <label for="project_url">URL project</label>
                            <input class="input" type="url" id="project_url" name="project_url" value="<?= e($value('project_url')) ?>" placeholder="https://…" maxlength="255">
                        </div>
                        <div class="field">
                            <label for="repo_url">URL repository</label>
                            <input class="input" type="url" id="repo_url" name="repo_url" value="<?= e($value('repo_url')) ?>" placeholder="https://github.com/…" maxlength="255">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div style="display:grid;gap:20px">
            <div class="card">
                <div class="card-head"><h2>Publikasi</h2></div>
                <div class="card-body form-section">
                    <div class="field">
                        <label for="status">Status</label>
                        <select class="select" id="status" name="status">
                            <option value="published" <?= $selected('status', 'published') ?>>Publik — tampil di portofolio</option>
                            <option value="draft" <?= $selected('status', 'draft') ?>>Draft — disembunyikan</option>
                        </select>
                    </div>

                    <label class="checkbox">
                        <input type="checkbox" name="is_featured" value="1" <?= (int) $value('is_featured', '0') === 1 ? 'checked' : '' ?>>
                        Jadikan project unggulan
                    </label>

                    <div class="field">
                        <label for="sort_order">Urutan tampil</label>
                        <input class="input" type="number" id="sort_order" name="sort_order" value="<?= e($value('sort_order', '0')) ?>" min="0">
                        <span class="hint">Angka kecil tampil lebih dulu.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Cover project</h2></div>
                <div class="card-body">
                    <div class="cover-editor">
                        <div class="upload-preview cover" id="project-cover-preview">
                            <?php if ($cover): ?>
                                <img src="<?= e($cover) ?>" alt="Cover project">
                            <?php else: ?>
                                <span><?= svg_icon('image') ?>Belum ada cover</span>
                            <?php endif; ?>
                        </div>

                        <?php if ($isEdit): ?>
                            <span class="hint text-center">Gunakan panel &quot;Ganti cover&quot; di bawah untuk memperbarui gambar.</span>
                        <?php else: ?>
                            <label class="btn btn-ghost btn-sm btn-block" style="cursor:pointer">
                                <?= svg_icon('image', 'icon-sm') ?> Pilih cover
                                <input type="file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" data-preview-input="#project-cover-preview">
                            </label>
                            <span class="hint text-center">JPG, PNG, WEBP, GIF · maks <?= \App\Uploader::maxUploadMb() ?> MB</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="form-actions" style="display:grid;gap:10px">
                <button class="btn btn-primary btn-block" type="submit">
                    <?= svg_icon('check', 'icon-sm') ?> <?= $isEdit ? 'Simpan perubahan' : 'Simpan project' ?>
                </button>
                <a class="btn btn-ghost btn-block" href="<?= e(app_url('/admin/projects')) ?>">Batal</a>
            </div>
        </div>
    </div>
</form>

<?php if ($isEdit): ?>
    <div class="grid-2 mt-3">
        <div class="card">
            <div class="card-head"><h2>Ganti cover</h2></div>
            <div class="card-body">
                <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/cover')) ?>" enctype="multipart/form-data" style="display:grid;gap:12px">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="cover-file">Pilih gambar cover baru</label>
                        <input class="input" type="file" id="cover-file" name="cover" accept="image/jpeg,image/png,image/webp,image/gif" required style="padding:9px">
                    </div>
                    <button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('image', 'icon-sm') ?> Unggah cover</button>
                </form>

                <?php if ($cover): ?>
                    <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/cover/remove')) ?>" class="mt-2"
                        data-confirm="Cover project akan dihapus. Kartu project akan memakai latar gradien.">
                        <?= csrf_field() ?>
                        <button class="btn btn-danger btn-sm btn-block" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus cover</button>
                    </form>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head">
                <h2>Tambah dokumentasi</h2>
                <div class="actions"><span class="badge badge-muted"><?= count($images) ?> gambar</span></div>
            </div>
            <div class="card-body">
                <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/gallery')) ?>" enctype="multipart/form-data" style="display:grid;gap:12px">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="images">Pilih gambar (bisa banyak)</label>
                        <input class="input" type="file" id="images" name="images[]" accept="image/jpeg,image/png,image/webp,image/gif" multiple required style="padding:9px">
                    </div>
                    <div class="field">
                        <label for="caption-0">Keterangan (opsional)</label>
                        <input class="input" type="text" id="caption-0" name="captions[0]" placeholder="Misal: Halaman dashboard" maxlength="200">
                        <span class="hint">Keterangan tiap gambar bisa disunting setelah diunggah.</span>
                    </div>
                    <button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('plus', 'icon-sm') ?> Unggah dokumentasi</button>
                </form>
            </div>
        </div>
    </div>

    <div class="card mt-2">
        <div class="card-head"><h2>Galeri dokumentasi</h2></div>
        <div class="card-body">
            <?php if ($images === []): ?>
                <div class="empty-state" style="padding:26px 12px">
                    <?= svg_icon('image') ?>
                    <h3>Belum ada dokumentasi</h3>
                    <p>Unggah tangkapan layar atau foto proses project agar pengunjung melihat hasil kerjanya.</p>
                </div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($images as $image): ?>
                        <div class="gallery-card">
                            <div class="img">
                                <img src="<?= e(media_url((string) $image['file_name']) ?? '') ?>" alt="<?= e((string) $image['caption']) ?>" loading="lazy">
                            </div>
                            <div class="meta">
                                <form method="post" action="<?= e(app_url('/admin/gallery/' . $image['id'] . '/caption')) ?>">
                                    <?= csrf_field() ?>
                                    <input class="input" type="text" name="caption" value="<?= e((string) $image['caption']) ?>" placeholder="Keterangan" maxlength="200">
                                    <button class="btn btn-ghost" type="submit" title="Simpan keterangan"><?= svg_icon('check', 'icon-sm') ?></button>
                                </form>
                                <form method="post" action="<?= e(app_url('/admin/gallery/' . $image['id'] . '/delete')) ?>"
                                    data-confirm="Gambar dokumentasi ini akan dihapus permanen." data-confirm-title="Hapus gambar?">
                                    <?= csrf_field() ?>
                                    <button class="btn btn-danger btn-block" type="submit" style="padding:6px"><?= svg_icon('trash', 'icon-sm') ?> Hapus</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card mt-2" style="border-color:rgba(251,113,133,.25)">
        <div class="card-body flex between flex-wrap">
            <div>
                <strong class="small">Zona berbahaya</strong>
                <p class="small muted mt-1">Menghapus project juga menghapus semua gambar dokumentasinya.</p>
            </div>
            <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/delete')) ?>"
                data-confirm="Project beserta seluruh dokumentasinya akan dihapus permanen." data-confirm-title="Hapus project?">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus project</button>
            </form>
        </div>
    </div>
<?php endif; ?>
