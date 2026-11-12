<?php
/** @var array<string, mixed> $user */
/** @var bool $enabled */
/** @var string|null $pendingSecret */
/** @var string|null $otpauthUri */
/** @var list<string> $recoveryCodes */
/** @var int $remainingCodes */
/** @var string $enabledAt */

$secretGroups = $pendingSecret !== null && $pendingSecret !== ''
    ? trim(chunk_split($pendingSecret, 4, ' '))
    : '';
?>
<div class="admin-head">
    <div>
        <span class="section-label">Keamanan</span>
        <h1 class="mt-1">Verifikasi dua langkah</h1>
        <p>Lindungi akun dengan kode 6 digit dari aplikasi authenticator (Google Authenticator, Authy, 1Password, Bitwarden).</p>
    </div>
    <span class="badge" style="background:<?= $enabled ? 'color-mix(in srgb, var(--success) 16%, transparent)' : 'color-mix(in srgb, var(--warning) 16%, transparent)' ?>">
        <?= $enabled ? 'Aktif' : 'Nonaktif' ?>
    </span>
</div>

<?php if ($recoveryCodes !== []): ?>
    <div class="card" style="margin-bottom:20px;border-color:var(--accent-line)">
        <div class="card-head"><h2>Simpan kode pemulihan ini sekarang</h2></div>
        <div class="card-body">
            <p class="small muted" style="margin-bottom:12px">
                Kode ini hanya ditampilkan sekali. Setiap kode bisa dipakai satu kali jika kamu kehilangan akses ke aplikasi authenticator.
            </p>
            <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:8px;font-family:var(--mono);font-size:14px">
                <?php foreach ($recoveryCodes as $code): ?>
                    <span style="padding:8px 10px;border:1px dashed var(--line-strong);border-radius:8px;text-align:center"><?= e($code) ?></span>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="grid-2" style="align-items:start">
    <div class="card">
        <div class="card-head"><h2>Status</h2></div>
        <div class="card-body">
            <div class="info-list" style="margin-top:0">
                <div class="info-item">
                    <span class="ii-icon"><?= svg_icon('lock') ?></span>
                    <div>
                        <small>2FA</small>
                        <span><?= $enabled ? 'Aktif sejak ' . e($enabledAt) : 'Belum diaktifkan' ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><?= svg_icon('user') ?></span>
                    <div>
                        <small>Akun</small>
                        <span><?= e((string) $user['email']) ?></span>
                    </div>
                </div>
                <div class="info-item">
                    <span class="ii-icon"><?= svg_icon('check') ?></span>
                    <div>
                        <small>Kode pemulihan tersisa</small>
                        <span><?= (int) $remainingCodes ?> kode</span>
                    </div>
                </div>
            </div>

            <?php if (!$enabled && ($pendingSecret === null || $pendingSecret === '')): ?>
                <form method="post" action="<?= e(app_url('/admin/security/setup')) ?>" style="margin-top:16px">
                    <?= csrf_field() ?>
                    <button class="btn btn-primary" type="submit"><?= svg_icon('lock', 'icon-sm') ?> Mulai aktifkan 2FA</button>
                </form>
            <?php endif; ?>

            <?php if ($enabled): ?>
                <div style="display:grid;gap:18px;margin-top:18px">
                    <form method="post" action="<?= e(app_url('/admin/security/recovery')) ?>" class="form-section">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="recovery_password">Kata sandi saat ini</label>
                            <input class="input" type="password" id="recovery_password" name="password" required autocomplete="current-password">
                            <span class="hint">Untuk membuat 8 kode pemulihan baru.</span>
                        </div>
                        <button class="btn btn-soft btn-sm" type="submit">Buat ulang kode pemulihan</button>
                    </form>

                    <form method="post" action="<?= e(app_url('/admin/security/disable')) ?>" class="form-section" style="border-top:1px dashed var(--line);padding-top:16px">
                        <?= csrf_field() ?>
                        <div class="field">
                            <label for="disable_password">Kata sandi</label>
                            <input class="input" type="password" id="disable_password" name="password" required autocomplete="current-password">
                        </div>
                        <div class="field">
                            <label for="disable_code">Kode 6 digit / kode pemulihan</label>
                            <input class="input" type="text" id="disable_code" name="code" required inputmode="numeric" autocomplete="one-time-code" maxlength="20">
                        </div>
                        <button class="btn btn-danger btn-sm" type="submit">Nonaktifkan 2FA</button>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <div class="card">
        <div class="card-head"><h2><?= $enabled ? 'Aplikasi authenticator' : 'Langkah pengaturan' ?></h2></div>
        <div class="card-body">
            <?php if (!$enabled && ($pendingSecret !== null && $pendingSecret !== '')): ?>
                <p class="small muted">Masukkan secret berikut ke aplikasi authenticator (pilih “Enter a setup key”), atau salin tautan di bawah.</p>

                <div class="field" style="margin-top:12px">
                    <label for="totp-secret">Secret</label>
                    <input class="input" id="totp-secret" type="text" value="<?= e($secretGroups) ?>" readonly style="font-family:var(--mono);letter-spacing:.08em">
                </div>

                <?php if ($otpauthUri !== null): ?>
                    <div class="field" style="margin-top:12px">
                        <label for="totp-uri">Tautan otpauth</label>
                        <textarea class="textarea" id="totp-uri" rows="3" readonly style="font-family:var(--mono);font-size:12px"><?= e($otpauthUri) ?></textarea>
                        <span class="hint">Bisa juga dibuka langsung di HP: salin tautan ini ke browser.</span>
                    </div>
                <?php endif; ?>

                <form method="post" action="<?= e(app_url('/admin/security/enable')) ?>" class="form-section" style="margin-top:16px">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="code">Kode 6 digit dari aplikasi <span class="hint">*</span></label>
                        <input class="input" type="text" id="code" name="code" required inputmode="numeric" pattern="[0-9]{6}" maxlength="6" autocomplete="one-time-code" placeholder="123456">
                    </div>
                    <button class="btn btn-primary" type="submit"><?= svg_icon('check', 'icon-sm') ?> Aktifkan 2FA</button>
                </form>
            <?php elseif ($enabled): ?>
                <p class="small muted">
                    Setiap kali masuk, kamu akan diminta kode 6 digit dari aplikasi authenticator.
                    Simpan kode pemulihan di tempat aman — kode itu satu-satunya jalan masuk bila ponsel hilang.
                </p>
                <p class="small muted" style="margin-top:10px">
                    Waktu perangkat harus akurat (disarankan sinkronisasi otomatis). Bila kode selalu ditolak, periksa jam HP.
                </p>
            <?php else: ?>
                <p class="small muted">
                    Klik <strong>Mulai aktifkan 2FA</strong> untuk membuat secret. Secret hanya ditampilkan di sesi ini dan baru tersimpan setelah kamu memasukkan kode yang benar.
                </p>
            <?php endif; ?>
        </div>
    </div>
</div>
