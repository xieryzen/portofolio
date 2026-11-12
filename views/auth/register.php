<?php /** @var bool $registrationOpen */ ?>
<h1>Buat akun baru</h1>
<p class="sub">Daftar untuk mengakses portofolio dan menyimpan akses Anda.</p>

<form class="auth-form" method="post" action="<?= e(app_url('/register')) ?>" novalidate>
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Nama lengkap</label>
        <div class="input-group">
            <?= svg_icon('user') ?>
            <input class="input <?= error_for('name') ? 'has-error' : '' ?>" type="text" id="name" name="name" value="<?= e(old('name')) ?>" placeholder="Nama Anda" autocomplete="name" required minlength="3" maxlength="100">
        </div>
        <?php if ($message = error_for('name')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="email">Email</label>
        <div class="input-group">
            <?= svg_icon('mail') ?>
            <input class="input <?= error_for('email') ? 'has-error' : '' ?>" type="email" id="email" name="email" value="<?= e(old('email')) ?>" placeholder="nama@email.com" autocomplete="email" required>
        </div>
        <?php if ($message = error_for('email')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password">Kata sandi</label>
        <div class="input-group">
            <?= svg_icon('lock') ?>
            <input class="input <?= error_for('password') ? 'has-error' : '' ?>" type="password" id="password" name="password" placeholder="Minimal 8 karakter" autocomplete="new-password" required minlength="8">
            <button class="toggle-password" type="button" data-toggle-password="password" aria-label="Tampilkan kata sandi"><?= svg_icon('eye') ?></button>
        </div>
        <?php if ($message = error_for('password')): ?><span class="error-text"><?= e($message) ?></span><?php endif; ?>
    </div>

    <div class="field">
        <label for="password_confirmation">Konfirmasi kata sandi</label>
        <div class="input-group">
            <?= svg_icon('lock') ?>
            <input class="input" type="password" id="password_confirmation" name="password_confirmation" placeholder="Ulangi kata sandi" autocomplete="new-password" required minlength="8">
        </div>
    </div>

    <button class="btn btn-primary btn-block" type="submit">Daftar <?= svg_icon('arrow-right') ?></button>
</form>

<p class="auth-foot">
    Sudah punya akun? <a href="<?= e(app_url('/login')) ?>">Masuk di sini</a>
</p>

<p class="auth-hint">
    Dengan mendaftar, akun Anda hanya memiliki akses untuk melihat portofolio. Pengelolaan konten hanya dapat dilakukan oleh owner.
</p>
