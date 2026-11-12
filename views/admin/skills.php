<?php
/** @var list<array<string, mixed>> $skills */
/** @var array<string, list<array<string, mixed>>> $grouped */

$categories = [];
foreach ($skills as $skill) {
    $categories[(string) $skill['category']] = true;
}
?>
<div class="admin-head">
    <div>
        <span class="section-label">Keahlian</span>
        <h1 class="mt-1">Keahlian & teknologi</h1>
        <p>Keahlian ditampilkan sebagai bar progres dan chip di halaman portofolio.</p>
    </div>
</div>

<div class="grid-main-side">
    <div class="card">
        <div class="card-head">
            <h2>Daftar keahlian</h2>
            <div class="actions"><span class="badge badge-muted"><?= count($skills) ?> item</span></div>
        </div>

        <?php if ($skills === []): ?>
            <div class="empty-state">
                <?= svg_icon('sparkles') ?>
                <h3>Belum ada keahlian</h3>
                <p>Tambahkan keahlian pertama lewat formulir di samping.</p>
            </div>
        <?php else: ?>
            <div class="table-wrap">
                <table class="table">
                    <thead>
                        <tr><th>Keahlian</th><th>Kategori</th><th style="width:150px">Level</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($skills as $skill): ?>
                            <tr>
                                <td>
                                    <form method="post" action="<?= e(app_url('/admin/skills/' . $skill['id'])) ?>" id="skill-<?= (int) $skill['id'] ?>" class="flex" style="gap:6px">
                                        <?= csrf_field() ?>
                                        <input class="input" type="text" name="name" value="<?= e((string) $skill['name']) ?>" maxlength="80" style="padding:7px 10px;font-size:13px" aria-label="Nama keahlian">
                                        <input type="hidden" name="category" value="<?= e((string) $skill['category']) ?>">
                                    </form>
                                </td>
                                <td class="small muted"><?= e((string) $skill['category']) ?></td>
                                <td>
                                    <input class="input" type="number" name="level" value="<?= (int) $skill['level'] ?>" min="0" max="100" form="skill-<?= (int) $skill['id'] ?>" style="padding:7px 10px;font-size:13px" aria-label="Level">
                                </td>
                                <td class="actions">
                                    <button class="icon-btn" type="submit" form="skill-<?= (int) $skill['id'] ?>" title="Simpan"><?= svg_icon('check') ?></button>
                                    <form method="post" action="<?= e(app_url('/admin/skills/' . $skill['id'] . '/delete')) ?>" style="display:inline"
                                        data-confirm="Keahlian ini akan dihapus dari portofolio." data-confirm-title="Hapus keahlian?">
                                        <?= csrf_field() ?>
                                        <button class="icon-btn is-danger" type="submit" title="Hapus"><?= svg_icon('trash') ?></button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>

    <div class="card">
        <div class="card-head"><h2>Tambah keahlian</h2></div>
        <div class="card-body">
            <form method="post" action="<?= e(app_url('/admin/skills')) ?>" class="form-section">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="name">Nama keahlian</label>
                    <input class="input" type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="Contoh: PHP 8.3" required maxlength="80">
                </div>

                <div class="field">
                    <label for="category">Kategori</label>
                    <select class="select" id="category" name="category" data-skill-category>
                        <?php foreach (array_keys($categories) as $category): ?>
                            <option value="<?= e((string) $category) ?>"><?= e((string) $category) ?></option>
                        <?php endforeach; ?>
                        <option value="Backend">Backend</option>
                        <option value="Frontend">Frontend</option>
                        <option value="Infrastruktur">Infrastruktur</option>
                        <option value="Desain">Desain</option>
                        <option value="__custom">+ Kategori baru…</option>
                    </select>
                    <input class="input hidden" type="text" name="category_custom" data-skill-category-custom placeholder="Nama kategori baru" maxlength="60">
                    <span class="hint">Pilih &quot;+ Kategori baru&quot; jika belum ada yang cocok.</span>
                </div>

                <div class="field">
                    <label for="level">Level (0–100)</label>
                    <input class="input" type="number" id="level" name="level" value="<?= e(old('level', '80')) ?>" min="0" max="100">
                </div>

                <button class="btn btn-primary btn-sm" type="submit"><?= svg_icon('plus', 'icon-sm') ?> Tambah keahlian</button>
            </form>
        </div>
    </div>
</div>
