<?php
/** @var array<string, mixed>|null $project */
/** @var list<array<string, mixed>> $images */
/** @var list<array<string, mixed>> $releases */

$isEdit = $project !== null;
$action = $isEdit ? '/admin/projects/' . $project['id'] : '/admin/projects';
$cover = $isEdit ? media_url($project['cover'] ?? null) : null;
$value = static fn (string $key, string $default = ''): string => (string) \old($key, $project[$key] ?? $default);
$selected = static fn (string $key, string $expected): string => $value($key) === $expected ? 'selected' : '';
$releases = $releases ?? [];
$releaseMaxMb = \App\Uploader::maxReleaseMb();
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
                <div class="card-head"><h2>Studi kasus</h2></div>
                <div class="card-body form-section">
                    <p class="small muted" style="margin-bottom:4px">
                        Opsional. Bagian ini tampil di halaman project sebagai “Studi kasus” dengan tiga blok dan kartu metrik.
                    </p>

                    <div class="field">
                        <label for="case_challenge">Tantangan</label>
                        <textarea class="textarea" id="case_challenge" name="case_challenge" rows="5" maxlength="4000" placeholder="Masalah atau kondisi awal yang dihadapi klien…"><?= e($value('case_challenge')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_solution">Solusi</label>
                        <textarea class="textarea" id="case_solution" name="case_solution" rows="5" maxlength="4000" placeholder="Pendekatan dan hal yang Anda kerjakan…"><?= e($value('case_solution')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_result">Hasil</label>
                        <textarea class="textarea" id="case_result" name="case_result" rows="4" maxlength="4000" placeholder="Dampak setelah project berjalan…"><?= e($value('case_result')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_metrics">Metrik (opsional)</label>
                        <textarea class="textarea" id="case_metrics" name="case_metrics" rows="4" maxlength="1200" placeholder="Waktu muat|1,2 detik&#10;Konversi|+38%&#10;Biaya server|-40%"><?= e($value('case_metrics')) ?></textarea>
                        <span class="hint">Satu metrik per baris dengan format <code>Label|Nilai</code>. Baris tanpa tanda <code>|</code> dianggap nilai saja.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Versi Inggris (opsional)</h2></div>
                <div class="card-body form-section">
                    <p class="small muted" style="margin-bottom:4px">
                        Dipakai saat pengunjung memilih bahasa Inggris. Kosongkan untuk memakai teks Indonesia.
                    </p>

                    <div class="field">
                        <label for="title_en">Judul (EN)</label>
                        <input class="input" type="text" id="title_en" name="title_en" value="<?= e($value('title_en')) ?>" maxlength="180" placeholder="Example: Online Store POS System">
                    </div>

                    <div class="field">
                        <label for="summary_en">Ringkasan (EN)</label>
                        <input class="input" type="text" id="summary_en" name="summary_en" value="<?= e($value('summary_en')) ?>" maxlength="300" placeholder="One sentence about the project">
                    </div>

                    <div class="field">
                        <label for="description_en">Deskripsi lengkap (EN)</label>
                        <textarea class="textarea" id="description_en" name="description_en" rows="8" maxlength="20000" placeholder="Background, challenge, solution, and result…"><?= e($value('description_en')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_challenge_en">Tantangan (EN)</label>
                        <textarea class="textarea" id="case_challenge_en" name="case_challenge_en" rows="3" maxlength="4000"><?= e($value('case_challenge_en')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_solution_en">Solusi (EN)</label>
                        <textarea class="textarea" id="case_solution_en" name="case_solution_en" rows="3" maxlength="4000"><?= e($value('case_solution_en')) ?></textarea>
                    </div>

                    <div class="field">
                        <label for="case_result_en">Hasil (EN)</label>
                        <textarea class="textarea" id="case_result_en" name="case_result_en" rows="3" maxlength="4000"><?= e($value('case_result_en')) ?></textarea>
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


    <div class="card mt-2" id="rilis">
        <div class="card-head">
            <h2>Rilis &amp; unduhan</h2>
            <div class="actions"><span class="badge badge-muted"><?= count($releases) ?> rilis</span></div>
        </div>
        <div class="card-body" style="display:grid;gap:20px">
            <p class="small muted">
                Unggah file versi project (ZIP source, APK, PDF, dan sebagainya) agar pengunjung bisa mengunduhnya.
                Maksimal <?= e(format_bytes($releaseMaxMb * 1024 * 1024)) ?> per file.
            </p>

            <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/releases')) ?>" enctype="multipart/form-data" class="release-form">
                <?= csrf_field() ?>
                <div class="form-row">
                    <div class="field">
                        <label for="release-version">Nomor versi <span class="hint">*</span></label>
                        <input class="input" type="text" id="release-version" name="version" placeholder="1.0.0" maxlength="40" required>
                    </div>
                    <div class="field">
                        <label for="release-title">Judul rilis</label>
                        <input class="input" type="text" id="release-title" name="title" placeholder="Rilis stabil pertama" maxlength="150">
                    </div>
                    <div class="field">
                        <label for="release-date">Tanggal rilis</label>
                        <input class="input" type="date" id="release-date" name="released_at" value="<?= e(date('Y-m-d')) ?>">
                    </div>
                </div>

                <div class="field">
                    <label for="release-notes">Catatan perubahan</label>
                    <textarea class="textarea" id="release-notes" name="notes" rows="4" maxlength="20000" placeholder="Contoh:&#10;- Menambahkan modul laporan penjualan&#10;- Perbaikan bug login"></textarea>
                    <span class="hint">Tulis satu poin per baris. Tampil di halaman publik sebagai changelog.</span>
                </div>

                <div class="field">
                    <label for="release-file">File rilis <span class="hint">(opsional)</span></label>
                    <input class="input" type="file" id="release-file" name="file" style="padding:9px">
                    <span class="hint">ZIP, RAR, 7Z, PDF, APK, TXT, MD, CSV, DOC/DOCX, XLS/XLSX, PPT/PPTX, PNG, JPG, WEBP, GIF · maks <?= $releaseMaxMb ?> MB</span>
                </div>

                <label class="checkbox">
                    <input type="checkbox" name="is_published" value="1" checked>
                    Tampilkan rilis ini di halaman publik
                </label>

                <div><button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('package', 'icon-sm') ?> Tambah rilis</button></div>
            </form>

            <?php if ($releases === []): ?>
                <div class="empty-state" style="padding:26px 12px">
                    <?= svg_icon('package') ?>
                    <h3>Belum ada rilis</h3>
                    <p>Tambahkan rilis pertama agar pengunjung bisa mengunduh hasil project Anda.</p>
                </div>
            <?php else: ?>
                <div class="release-admin-list">
                    <?php foreach ($releases as $release): ?>
                        <div class="release-admin-item">
                            <div class="release-admin-head">
                                <div style="min-width:0">
                                    <div class="release-version">
                                        <strong>v<?= e((string) $release['version']) ?></strong>
                                        <?php if ((int) $release['is_published'] === 1): ?>
                                            <span class="badge badge-success">Publik</span>
                                        <?php else: ?>
                                            <span class="badge badge-amber">Draft</span>
                                        <?php endif; ?>
                                        <span class="badge badge-muted"><?= e(release_file_label($release['original_name'] ?? null, $release['file_name'] ?? null)) ?></span>
                                        <?php if (!empty($release['title'])): ?>
                                            <span class="small muted"><?= e((string) $release['title']) ?></span>
                                        <?php endif; ?>
                                    </div>
                                    <div class="release-meta">
                                        <span><?= svg_icon('calendar', 'icon-sm') ?> <?= e(format_date($release['released_at'], 'd M Y')) ?></span>
                                        <span><?= svg_icon('package', 'icon-sm') ?> <?= $release['file_name'] ? e(format_bytes((int) $release['file_size'])) : 'Tanpa file' ?></span>
                                        <span><?= svg_icon('download', 'icon-sm') ?> <?= number_format((int) $release['downloads'], 0, ',', '.') ?> unduhan</span>
                                    </div>
                                </div>
                                <div class="release-actions">
                                    <?php if ($release['file_name']): ?>
                                        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/download/' . $release['id'])) ?>"><?= svg_icon('download', 'icon-sm') ?> Unduh</a>
                                    <?php endif; ?>
                                    <form method="post" action="<?= e(app_url('/admin/releases/' . $release['id'] . '/status')) ?>">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-ghost btn-sm" type="submit"><?= (int) $release['is_published'] === 1 ? 'Sembunyikan' : 'Publikasikan' ?></button>
                                    </form>
                                    <form method="post" action="<?= e(app_url('/admin/releases/' . $release['id'] . '/delete')) ?>"
                                        data-confirm="Rilis ini beserta file unduhannya akan dihapus permanen." data-confirm-title="Hapus rilis?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-sm" type="submit" title="Hapus rilis"><?= svg_icon('trash', 'icon-sm') ?></button>
                                    </form>
                                </div>
                            </div>

                            <?php if (!empty($release['notes'])): ?>
                                <p class="release-notes"><?= nl2br(e((string) $release['notes'])) ?></p>
                            <?php endif; ?>

                            <details class="release-edit">
                                <summary>Edit rilis</summary>
                                <form method="post" action="<?= e(app_url('/admin/releases/' . $release['id'])) ?>" enctype="multipart/form-data">
                                    <?= csrf_field() ?>
                                    <div class="form-row">
                                        <div class="field">
                                            <label for="rv-<?= (int) $release['id'] ?>">Nomor versi</label>
                                            <input class="input" type="text" id="rv-<?= (int) $release['id'] ?>" name="version" value="<?= e((string) $release['version']) ?>" maxlength="40" required>
                                        </div>
                                        <div class="field">
                                            <label for="rt-<?= (int) $release['id'] ?>">Judul rilis</label>
                                            <input class="input" type="text" id="rt-<?= (int) $release['id'] ?>" name="title" value="<?= e((string) ($release['title'] ?? '')) ?>" maxlength="150">
                                        </div>
                                        <div class="field">
                                            <label for="rd-<?= (int) $release['id'] ?>">Tanggal rilis</label>
                                            <input class="input" type="date" id="rd-<?= (int) $release['id'] ?>" name="released_at" value="<?= e((string) $release['released_at']) ?>">
                                        </div>
                                    </div>
                                    <div class="field">
                                        <label for="rn-<?= (int) $release['id'] ?>">Catatan perubahan</label>
                                        <textarea class="textarea" id="rn-<?= (int) $release['id'] ?>" name="notes" rows="4" maxlength="20000"><?= e((string) ($release['notes'] ?? '')) ?></textarea>
                                    </div>
                                    <div class="field">
                                        <label for="rf-<?= (int) $release['id'] ?>">Ganti file <span class="hint">(biarkan kosong jika tidak diganti)</span></label>
                                        <input class="input" type="file" id="rf-<?= (int) $release['id'] ?>" name="file" style="padding:9px">
                                        <span class="hint"><?= $release['file_name'] ? e('File saat ini: ' . (string) ($release['original_name'] ?: $release['file_name'])) : 'Belum ada file.' ?></span>
                                    </div>
                                    <label class="checkbox">
                                        <input type="checkbox" name="is_published" value="1" <?= (int) $release['is_published'] === 1 ? 'checked' : '' ?>>
                                        Tampilkan di halaman publik
                                    </label>
                                    <div class="release-actions">
                                        <button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('check', 'icon-sm') ?> Simpan rilis</button>
                                    </div>
                                </form>

                                <?php if ($release['file_name']): ?>
                                    <form method="post" action="<?= e(app_url('/admin/releases/' . $release['id'] . '/file/remove')) ?>"
                                        data-confirm="File rilis akan dihapus, catatan versinya tetap tersimpan." data-confirm-title="Hapus file?">
                                        <?= csrf_field() ?>
                                        <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus file saja</button>
                                    </form>
                                <?php endif; ?>
                            </details>
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
                <p class="small muted mt-1">Menghapus project juga menghapus semua gambar dokumentasi dan file rilisnya.</p>
            </div>
            <form method="post" action="<?= e(app_url('/admin/projects/' . $project['id'] . '/delete')) ?>"
                data-confirm="Project beserta seluruh dokumentasi dan file rilisnya akan dihapus permanen." data-confirm-title="Hapus project?">
                <?= csrf_field() ?>
                <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus project</button>
            </form>
        </div>
    </div>
<?php endif; ?>
