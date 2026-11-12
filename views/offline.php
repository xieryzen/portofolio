<?php
/** @var array<string, mixed> $profile */
?>
<section class="section" style="min-height:calc(100vh - 220px);display:grid;place-items:center">
    <div class="container" style="max-width:560px">
        <div class="card" style="padding:34px 28px;">
            <span class="section-label">Mode offline</span>
            <h1 class="mt-1" style="font-size:clamp(1.5rem,3.4vw,2rem)">Kamu sedang tidak terhubung</h1>
            <p class="mt-2" style="color:var(--muted)">
                Halaman ini tersimpan di perangkatmu, jadi situs tetap bisa dibuka tanpa internet.
                Begitu koneksi kembali, konten terbaru akan dimuat otomatis.
            </p>
            <div class="flex flex-wrap mt-3" style="gap:10px">
                <a class="btn btn-primary" href="<?= e(app_url('/')) ?>">Coba muat ulang</a>
                <?php if (!empty($profile['full_name'])): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('/#kontak')) ?>">Halaman kontak</a>
                <?php endif; ?>
            </div>
            <p class="hint mt-3">Tips: sambungkan kembali Wi-Fi/data seluler, lalu tekan tombol di atas.</p>
        </div>
    </div>
</section>
