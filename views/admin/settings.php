<?php
/** @var array<string, mixed> $user */
/** @var array{telegram: bool, webhook: bool, email: bool} $notifyChannels */
/** @var bool $notifyEnabled */
/** @var list<array{time: string, channel: string, ok: bool, detail: string}> $notifyLogs */

$value = static fn (string $key, string $default = ''): string => (string) \old($key, setting($key, $default));
?>
<div class="admin-head">
    <div>
        <span class="section-label">Pengaturan</span>
        <h1 class="mt-1">Pengaturan situs</h1>
        <p>Atur identitas situs, tampilan, dan akun owner. Khusus pemilik situs.</p>
    </div>
</div>

<div class="grid-main-side">
    <form method="post" action="<?= e(app_url('/admin/settings')) ?>" style="display:grid;gap:20px" novalidate>
        <?= csrf_field() ?>

        <div class="card">
            <div class="card-head"><h2>Identitas situs</h2></div>
            <div class="card-body form-section">
                <div class="form-row">
                    <div class="field">
                        <label for="site_title">Judul situs <span class="hint">*</span></label>
                        <input class="input <?= error_for('site_title') ? 'has-error' : '' ?>" type="text" id="site_title" name="site_title" value="<?= e($value('site_title', 'Portfolio')) ?>" required maxlength="80">
                        <span class="hint">Tampil di header, footer, dan judul tab browser.</span>
                    </div>
                    <div class="field">
                        <label for="accent_color">Warna aksen</label>
                        <div class="flex" style="gap:10px">
                            <input type="color" id="accent_color" name="accent_color" value="<?= e($value('accent_color', '#6d8bff')) ?>" style="width:52px;height:42px;padding:4px;border-radius:10px;border:1px solid var(--line-strong);background:rgba(6,11,22,.6);cursor:pointer">
                            <input class="input" type="text" value="<?= e($value('accent_color', '#6d8bff')) ?>" readonly aria-label="Kode warna aksen" style="font-family:var(--mono);font-size:13px">
                        </div>
                    </div>
                    <div class="field full">
                        <label for="meta_description">Deskripsi meta</label>
                        <textarea class="textarea" id="meta_description" name="meta_description" rows="3" maxlength="300"><?= e($value('meta_description')) ?></textarea>
                        <span class="hint">Dipakai mesin pencari saat menampilkan situs Anda.</span>
                    </div>
                    <div class="field full">
                        <label for="footer_text">Teks footer</label>
                        <input class="input" type="text" id="footer_text" name="footer_text" value="<?= e($value('footer_text')) ?>" maxlength="200">
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Fitur situs</h2></div>
            <div class="card-body form-section">
                <label class="checkbox">
                    <input type="checkbox" name="show_contact" value="1" <?= setting('show_contact', '1') === '1' ? 'checked' : '' ?>>
                    Tampilkan formulir kontak di halaman portofolio
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="allow_registration" value="1" <?= setting('allow_registration', '1') === '1' ? 'checked' : '' ?>>
                    Izinkan pengunjung mendaftar akun user
                </label>
                <label class="checkbox">
                    <input type="checkbox" name="notify_contact" value="1" <?= $notifyEnabled ? 'checked' : '' ?>>
                    Kirim notifikasi saat ada pesan kontak baru
                </label>
                <p class="small muted">
                    Catatan: nilai <code>ALLOW_REGISTRATION</code> di file <code>.env</code> juga harus bernilai <code>true</code> agar pendaftaran aktif.
                </p>
            </div>
        </div>

        <div class="form-actions">
            <button class="btn btn-primary" type="submit"><?= svg_icon('check', 'icon-sm') ?> Simpan pengaturan</button>
        </div>
    </form>

    <div style="display:grid;gap:20px">
        <div class="card">
            <div class="card-head"><h2>Akun owner</h2></div>
            <div class="card-body">
                <form method="post" action="<?= e(app_url('/admin/settings/password')) ?>" class="form-section" novalidate>
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="name">Nama</label>
                        <input class="input" type="text" id="name" name="name" value="<?= e(\old('name', (string) $user['name'])) ?>" required maxlength="120">
                    </div>
                    <div class="field">
                        <label for="email">Email login</label>
                        <input class="input" type="email" id="email" name="email" value="<?= e(\old('email', (string) $user['email'])) ?>" required maxlength="190">
                    </div>
                    <div class="field">
                        <label for="current_password">Kata sandi saat ini <span class="hint">*</span></label>
                        <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                        <span class="hint">Wajib diisi untuk menyimpan perubahan.</span>
                    </div>
                    <div class="field">
                        <label for="password">Kata sandi baru</label>
                        <input class="input" type="password" id="password" name="password" autocomplete="new-password" minlength="8">
                        <span class="hint">Kosongkan jika tidak ingin mengganti kata sandi.</span>
                    </div>
                    <button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('lock', 'icon-sm') ?> Perbarui akun</button>
                </form>
            </div>
        </div>

        <div class="card" id="notifikasi">
            <div class="card-head">
                <h2>Notifikasi pesan</h2>
                <span class="badge"><?= $notifyEnabled ? 'aktif' : 'nonaktif' ?></span>
            </div>
            <div class="card-body">
                <p class="small muted" style="margin-bottom:12px">
                    Kanal diatur lewat <code>.env</code> (butuh restart server setelah diubah).
                </p>

                <div class="info-list" style="margin-top:0">
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('message') ?></span>
                        <div>
                            <small>Telegram</small>
                            <span><?= $notifyChannels['telegram'] ? 'Siap — TELEGRAM_BOT_TOKEN &amp; CHAT_ID terisi' : 'Belum diatur' ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('external') ?></span>
                        <div>
                            <small>Webhook</small>
                            <span><?= $notifyChannels['webhook'] ? 'Siap — NOTIFY_WEBHOOK_URL terisi' : 'Belum diatur' ?></span>
                        </div>
                    </div>
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('mail') ?></span>
                        <div>
                            <small>Email</small>
                            <span><?= $notifyChannels['email'] ? 'Siap — ' . e((string) (\App\Config::env('NOTIFY_EMAIL_TO', '') ?: 'email owner')) : 'Belum diatur' ?></span>
                        </div>
                    </div>
                </div>

                <form method="post" action="<?= e(app_url('/admin/settings/test-notify')) ?>" style="margin-top:14px">
                    <?= csrf_field() ?>
                    <button class="btn btn-soft btn-sm" type="submit"><?= svg_icon('sparkles', 'icon-sm') ?> Kirim tes notifikasi</button>
                </form>

                <?php if ($notifyLogs !== []): ?>
                    <p class="side-label" style="margin:18px 0 8px">Percobaan terakhir</p>
                    <div class="table-wrap">
                        <table class="table">
                            <tbody>
                            <?php foreach ($notifyLogs as $log): ?>
                                <tr>
                                    <td style="white-space:nowrap"><?= e($log['time']) ?></td>
                                    <td><?= e($log['channel']) ?></td>
                                    <td>
                                        <span class="badge" style="background:<?= $log['ok'] ? 'color-mix(in srgb, var(--success) 18%, transparent)' : 'color-mix(in srgb, var(--danger) 16%, transparent)' ?>">
                                            <?= $log['ok'] ? 'OK' : 'Gagal' ?>
                                        </span>
                                        <br><small class="muted"><?= e($log['detail']) ?></small>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="card">
            <div class="card-head"><h2>Informasi sistem</h2></div>
            <div class="card-body">
                <div class="info-list" style="margin-top:0">
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('sparkles') ?></span>
                        <div><small>PHP</small><span><?= e(PHP_VERSION) ?></span></div>
                    </div>
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('dashboard') ?></span>
                        <div><small>Database</small><span><?= e(\App\Database::driver()) ?></span></div>
                    </div>
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('image') ?></span>
                        <div><small>Penyimpanan</small><span><?= e(ini_get('upload_max_filesize') ?: '-') ?> per file</span></div>
                    </div>
                    <div class="info-item">
                        <span class="ii-icon"><?= svg_icon('clock') ?></span>
                        <div><small>Zona waktu</small><span><?= e(date_default_timezone_get()) ?></span></div>
                    </div>
                </div>
                <p class="small muted mt-2">Konfigurasi koneksi database &amp; kredensial awal diatur melalui file <code>.env</code>.</p>
            </div>
        </div>
    </div>
</div>
