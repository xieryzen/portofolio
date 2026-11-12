<section class="section" style="min-height:60vh;display:grid;place-items:center">
    <div class="container" style="text-align:center;max-width:620px">
        <div class="eyebrow" style="margin-inline:auto"><span class="dot"></span> Error 404</div>
        <h1 style="font-size:clamp(2.2rem,6vw,3.6rem);margin-top:22px">Halaman tidak ditemukan</h1>
        <p class="muted mt-2">Mungkin tautannya salah, atau halaman sudah dipindahkan. Mari kembali ke portofolio.</p>
        <div class="flex" style="justify-content:center;gap:12px;margin-top:28px;flex-wrap:wrap">
            <a class="btn btn-primary" href="<?= e(app_url('/')) ?>"><?= svg_icon('arrow-left') ?> Kembali ke beranda</a>
            <a class="btn btn-ghost" href="<?= e(app_url('/#karya')) ?>">Lihat karya</a>
        </div>
    </div>
</section>
