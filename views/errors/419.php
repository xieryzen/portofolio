<section class="section" style="min-height:60vh;display:grid;place-items:center">
    <div class="container" style="text-align:center;max-width:620px">
        <div class="eyebrow" style="margin-inline:auto"><span class="dot" style="background:var(--warning)"></span> Sesi kedaluwarsa</div>
        <h1 style="font-size:clamp(2rem,5vw,3rem);margin-top:22px">Formulir perlu dimuat ulang</h1>
        <p class="muted mt-2">Demi keamanan, token formulir sudah kedaluwarsa. Silakan kembali, muat ulang halaman, lalu kirim lagi.</p>
        <div class="flex" style="justify-content:center;gap:12px;margin-top:28px;flex-wrap:wrap">
            <a class="btn btn-primary" href="<?= e(app_url('/')) ?>">Kembali ke beranda</a>
            <a class="btn btn-ghost" href="<?= e(app_url('/login')) ?>">Masuk ulang</a>
        </div>
    </div>
</section>
