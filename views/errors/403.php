<section class="section" style="min-height:60vh;display:grid;place-items:center">
    <div class="container" style="text-align:center;max-width:620px">
        <div class="eyebrow" style="margin-inline:auto"><span class="dot" style="background:var(--danger);box-shadow:0 0 0 4px rgba(251,113,133,.15)"></span> Akses ditolak</div>
        <h1 style="font-size:clamp(2rem,5vw,3rem);margin-top:22px">Anda tidak punya akses</h1>
        <p class="muted mt-2"><?= e($message ?? 'Halaman ini khusus untuk pengelola situs. Silakan masuk dengan akun yang sesuai.') ?></p>
        <div class="flex" style="justify-content:center;gap:12px;margin-top:28px;flex-wrap:wrap">
            <a class="btn btn-primary" href="<?= e(app_url('/')) ?>">Kembali ke portofolio</a>
            <?php if (!$authUser): ?>
                <a class="btn btn-ghost" href="<?= e(app_url('/login')) ?>">Masuk</a>
            <?php endif; ?>
        </div>
    </div>
</section>
