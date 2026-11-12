<?php
/** @var array<string, mixed>|null $post */

$isEdit = $post !== null;
$action = $isEdit ? '/admin/posts/' . $post['id'] : '/admin/posts';
$cover = $isEdit ? media_url($post['cover'] ?? null) : null;
$value = static fn (string $key, string $default = ''): string => (string) \old($key, $post[$key] ?? $default);
$publishedAtValue = (string) \old('published_at', isset($post['published_at']) && $post['published_at'] ? date('Y-m-d\TH:i', strtotime((string) $post['published_at']) ?: time()) : '');
$isScheduled = ($post['status'] ?? '') === 'published' && !empty($post['published_at']) && strtotime((string) $post['published_at']) > time();
$categories = ['Artikel', 'Tutorial', 'Catatan', 'Opini', 'Kabar'];
?>
<div class="admin-head">
    <div>
        <span class="section-label"><?= $isEdit ? 'Edit artikel' : 'Artikel baru' ?></span>
        <h1 class="mt-1"><?= $isEdit ? e((string) $post['title']) : 'Tulis artikel' ?></h1>
        <p><?= $isEdit ? 'Perbarui isi, cover, atau status publikasi.' : 'Isi judul dan konten. Artikel bisa disimpan sebagai draft dulu.' ?></p>
    </div>
    <div class="actions">
        <?php if ($isEdit && ($post['status'] ?? '') === 'published'): ?>
            <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/blog/' . $post['slug'])) ?>" target="_blank" rel="noopener"><?= svg_icon('eye', 'icon-sm') ?> Pratinjau</a>
        <?php endif; ?>
        <a class="btn btn-ghost btn-sm" href="<?= e(app_url('/admin/posts')) ?>"><?= svg_icon('arrow-left', 'icon-sm') ?> Kembali</a>
    </div>
</div>

<form method="post" action="<?= e(app_url($action)) ?>" enctype="multipart/form-data" novalidate>
    <?= csrf_field() ?>

    <div class="grid-main-side">
        <div style="display:grid;gap:20px">
            <div class="card">
                <div class="card-head"><h2>Isi artikel</h2></div>
                <div class="card-body form-section">
                    <div class="field">
                        <label for="title">Judul <span class="hint">*</span></label>
                        <input class="input <?= error_for('title') ? 'has-error' : '' ?>" type="text" id="title" name="title"
                               value="<?= e($value('title')) ?>" required maxlength="180" placeholder="Contoh: Cara mempercepat website PHP">
                        <span class="hint">Slug URL dibuat otomatis dari judul.</span>
                    </div>

                    <div class="field">
                        <label for="excerpt">Ringkasan</label>
                        <input class="input" type="text" id="excerpt" name="excerpt" value="<?= e($value('excerpt')) ?>"
                               maxlength="300" placeholder="Satu kalimat yang muncul di daftar artikel">
                    </div>

                    <div class="field">
                        <label for="content">Konten <span class="hint">*</span></label>
                        <textarea class="textarea" id="content" name="content" rows="22" maxlength="60000"
                                  placeholder="Tulis artikel di sini. Pisahkan paragraf dengan baris kosong."><?= e($value('content')) ?></textarea>
                        <span class="hint">Teks biasa (aman dari HTML), paragraf dipisahkan baris kosong. Estimasi waktu baca dihitung otomatis.</span>
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
                            <option value="draft" <?= $value('status', 'draft') === 'draft' ? 'selected' : '' ?>>Draft — belum tampil</option>
                            <option value="published" <?= $value('status') === 'published' ? 'selected' : '' ?>>Publik — tampil di blog</option>
                        </select>
                        <span class="hint">Status Publik dengan tanggal di masa depan otomatis menjadi <strong>terjadwal</strong>.</span>
                    </div>

                    <div class="field">
                        <label for="published_at">Tanggal publikasi</label>
                        <input class="input" type="datetime-local" id="published_at" name="published_at" value="<?= e($publishedAtValue) ?>">
                        <span class="hint">Kosongkan untuk memakai waktu sekarang (saat pertama dipublikasikan). Isi tanggal ke depan untuk menjadwalkan.</span>
                    </div>

                    <?php if ($isScheduled): ?>
                        <p class="small" style="color:var(--warning)">
                            <?= svg_icon('clock', 'icon-sm') ?> Terjadwal: terbit <?= e(format_date((string) $post['published_at'], 'd M Y H:i')) ?>.
                        </p>
                    <?php endif; ?>

                    <label class="checkbox">
                        <input type="checkbox" name="is_featured" value="1" <?= (int) $value('is_featured', '0') === 1 ? 'checked' : '' ?>>
                        Jadikan artikel pilihan
                    </label>

                    <div class="field" style="margin-top:12px">
                        <label for="category">Kategori</label>
                        <input class="input" type="text" id="category" name="category" value="<?= e($value('category', 'Artikel')) ?>"
                               list="post-category-options" maxlength="80">
                        <datalist id="post-category-options">
                            <?php foreach ($categories as $category): ?><option value="<?= e($category) ?>"></option><?php endforeach; ?>
                        </datalist>
                    </div>

                    <div class="field">
                        <label for="tags">Tag</label>
                        <input class="input" type="text" id="tags" name="tags" value="<?= e($value('tags')) ?>"
                               maxlength="200" placeholder="php, performa, hosting">
                        <span class="hint">Pisahkan dengan koma.</span>
                    </div>

                    <?php if ($isEdit && (int) ($post['reading_minutes'] ?? 0) > 0): ?>
                        <p class="small muted">Estimasi baca: <?= (int) $post['reading_minutes'] ?> menit · <?= number_format((int) $post['views'], 0, ',', '.') ?> kali dilihat</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Versi Inggris (opsional)</h2></div>
                <div class="card-body form-section">
                    <p class="small muted" style="margin-bottom:4px">
                        Tampil saat pengunjung memilih bahasa Inggris. Kosongkan untuk memakai teks Indonesia.
                    </p>

                    <div class="field">
                        <label for="title_en">Judul (EN)</label>
                        <input class="input" type="text" id="title_en" name="title_en" value="<?= e($value('title_en')) ?>" maxlength="180" placeholder="How to speed up a PHP website">
                    </div>

                    <div class="field">
                        <label for="excerpt_en">Ringkasan (EN)</label>
                        <input class="input" type="text" id="excerpt_en" name="excerpt_en" value="<?= e($value('excerpt_en')) ?>" maxlength="300">
                    </div>

                    <div class="field">
                        <label for="content_en">Konten (EN)</label>
                        <textarea class="textarea" id="content_en" name="content_en" rows="12" maxlength="60000" placeholder="Write the English version here…"><?= e($value('content_en')) ?></textarea>
                        <span class="hint">Paragraf dipisah baris kosong.</span>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Cover</h2></div>
                <div class="card-body">
                    <div class="upload-preview cover" id="post-cover-preview" data-preview-target="post-cover-preview">
                        <?php if ($cover): ?>
                            <img src="<?= e($cover) ?>" alt="Cover artikel">
                        <?php else: ?>
                            <span><?= svg_icon('image') ?>Belum ada cover</span>
                        <?php endif; ?>
                    </div>

                    <div class="field mt-2">
                        <label for="post-cover-file" class="hint">Pilih gambar cover</label>
                        <input class="input" type="file" id="post-cover-file" name="cover"
                               accept="image/jpeg,image/png,image/webp,image/gif" style="padding:9px"
                               data-preview-input="#post-cover-preview">
                    </div>
                    <span class="hint">JPG, PNG, WEBP, GIF · maks <?= \App\Uploader::maxUploadMb() ?> MB. Otomatis dioptimasi ke WebP.</span>

                    <?php if ($cover): ?>
                        <button class="btn btn-danger btn-sm btn-block mt-2" type="submit"
                                formaction="<?= e(app_url('/admin/posts/' . $post['id'] . '/cover/delete')) ?>"
                                formnovalidate data-confirm="Hapus cover artikel?">
                            <?= svg_icon('trash', 'icon-sm') ?> Hapus cover
                        </button>
                    <?php endif; ?>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Simpan</h2></div>
                <div class="card-body form-section">
                    <button class="btn btn-primary btn-block" type="submit"><?= svg_icon('check', 'icon-sm') ?> Simpan artikel</button>
                    <a class="btn btn-ghost btn-block" href="<?= e(app_url('/admin/posts')) ?>">Batal</a>
                </div>
            </div>
        </div>
    </div>
</form>

<?php if ($isEdit): ?>
    <form method="post" action="<?= e(app_url('/admin/posts/' . $post['id'] . '/delete')) ?>" class="mt-3"
          data-confirm="Hapus artikel ini permanen?">
        <?= csrf_field() ?>
        <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus artikel</button>
    </form>
<?php endif; ?>
