<?php
/** @var string $title */
?>
<div class="auth-main" style="display:grid;place-items:center">
    <div class="auth-card" style="max-width:440px;width:100%">
        <div class="auth-head">
            <span class="section-label">Keamanan</span>
            <h1>Verifikasi dua langkah</h1>
            <p class="muted">Masukkan kode 6 digit dari aplikasi authenticator, atau salah satu kode pemulihan.</p>
        </div>

        <form method="post" action="<?= e(app_url('/two-factor')) ?>" class="form-section" novalidate>
            <?= csrf_field() ?>

            <div class="field">
                <label for="code">Kode verifikasi <span class="hint">*</span></label>
                <input class="input <?= error_for('code') ? 'has-error' : '' ?>" type="text" id="code" name="code"
                       inputmode="numeric" autocomplete="one-time-code" maxlength="20" required autofocus
                       placeholder="123456 atau ABCDE-12345">
                <span class="hint">Kode pemulihan berbentuk 5 karakter, tanda hubung, 5 karakter.</span>
            </div>

            <button class="btn btn-primary btn-block" type="submit">
                <?= svg_icon('lock', 'icon-sm') ?> Verifikasi &amp; masuk
            </button>
        </form>

        <form method="post" action="<?= e(app_url('/two-factor/cancel')) ?>" style="margin-top:14px">
            <?= csrf_field() ?>
            <button class="btn btn-ghost btn-sm btn-block" type="submit">Batal, kembali ke halaman masuk</button>
        </form>
    </div>
</div>
