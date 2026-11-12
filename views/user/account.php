<?php
/** @var array<string, mixed> $user */
?>
<section class="section-sm" style="padding-top:44px">
    <div class="container" style="max-width:860px">
        <a class="back-link" href="<?= e(app_url('/dashboard')) ?>"><?= svg_icon('arrow-left') ?> Kembali ke dashboard</a>

        <div class="admin-head">
            <div>
                <span class="section-label">Akun</span>
                <h1 class="mt-1">Pengaturan akun</h1>
                <p>Kelola informasi akun dan kata sandi Anda.</p>
            </div>
        </div>

        <div class="grid-2">
            <div class="card">
                <div class="card-head"><h2>Informasi akun</h2></div>
                <div class="card-body">
                    <div class="info-list">
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('user') ?></span>
                            <div><small>Nama</small><span><?= e((string) $user['name']) ?></span></div>
                        </div>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('mail') ?></span>
                            <div><small>Email</small><span><?= e((string) $user['email']) ?></span></div>
                        </div>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('sparkles') ?></span>
                            <div><small>Role</small><span style="text-transform:capitalize"><?= e((string) $user['role']) ?></span></div>
                        </div>
                        <div class="info-item">
                            <span class="ii-icon"><?= svg_icon('clock') ?></span>
                            <div><small>Terakhir masuk</small><span><?= e(time_ago($user['last_login_at'] ?? null)) ?></span></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-head"><h2>Ubah kata sandi</h2></div>
                <div class="card-body">
                    <form method="post" action="<?= e(app_url('/profil/password')) ?>" class="form-grid" style="grid-template-columns:1fr">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="current_password">Kata sandi saat ini</label>
                            <input class="input" type="password" id="current_password" name="current_password" autocomplete="current-password" required>
                        </div>
                        <div class="field">
                            <label for="new_password">Kata sandi baru</label>
                            <input class="input" type="password" id="new_password" name="password" autocomplete="new-password" minlength="8" required>
                            <span class="hint">Minimal 8 karakter.</span>
                        </div>
                        <div class="field">
                            <label for="password_confirmation">Konfirmasi kata sandi baru</label>
                            <input class="input" type="password" id="password_confirmation" name="password_confirmation" autocomplete="new-password" minlength="8" required>
                        </div>
                        <button class="btn btn-primary" type="submit">Simpan kata sandi</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
