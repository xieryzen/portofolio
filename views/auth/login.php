<?php
/** @var string $next */
?>
<h1>Selamat datang kembali</h1>
<p class="sub">Masuk untuk melihat portofolio dan mengelola konten (khusus owner).</p>

<?php if ($message = error_for('email')): ?>
    <div class="toast toast-error mt-2" style="position:static;animation:none">
        <?= svg_icon('alert', 'icon') ?>
        <div><?= e($message) ?></div>
    </div>
<?php endif; ?>

<form class="auth-form" method="post" action="<?= e(app_url('/login')) ?>" novalidate>
    <?= csrf_field() ?>
    <?php if ($next !== ''): ?>
        <input type="hidden" name="next" value="<?= e($next) ?>">
    <?php endif; ?>

    <div class="field">
        <label for="email">Email</label>
        <div class="input-group">
            <?= svg_icon('mail') ?>
            <input class="input <?= error_for('email') ? 'has-error' : '' ?>" type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="nama@email.com" autocomplete="email" required autofocus>
        </div>
    </div>

    <div class="field">
        <label for="password">Kata sandi</label>
        <div class="input-group">
            <?= svg_icon('lock') ?>
            <input class="input" type="password" id="password" name="password" placeholder="••••••••" autocomplete="current-password" required minlength="6">
            <button class="toggle-password" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi"><?= svg_icon('eye') ?></button>
        </div>
    </div>

    <button class="btn btn-primary btn-block" type="submit">Masuk <?= svg_icon('arrow-right') ?></button>
</form>

<p class="auth-foot">
    Belum punya akun?
    <?php if ($registrationOpen): ?>
        <a href="<?= e(app_url('/register')) ?>">Daftar sekarang</a>
    <?php else: ?>
        <span class="muted">Pendaftaran sedang ditutup.</span>
    <?php endif; ?>
</p>

<p class="auth-foot" style="margin-top:10px">
    <a href="<?= e(app_url('/')) ?>" class="muted" style="font-weight:500">← Kembali ke portofolio</a>
</p>
