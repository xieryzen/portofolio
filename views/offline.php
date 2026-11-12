<?php
/** @var array<string, mixed> $profile */
?>
<section class="section" style="min-height:calc(100vh - 220px);display:grid;place-items:center">
    <div class="container" style="max-width:560px">
        <div class="card" style="padding:34px 28px;">
            <span class="section-label"><?= e(t('offline.label')) ?></span>
            <h1 class="mt-1" style="font-size:clamp(1.5rem,3.4vw,2rem)"><?= e(t('offline.title')) ?></h1>
            <p class="mt-2" style="color:var(--muted)">
                <?= e(t('offline.desc')) ?>
            </p>
            <div class="flex flex-wrap mt-3" style="gap:10px">
                <a class="btn btn-primary" href="<?= e(app_url('/')) ?>"><?= e(t('offline.reload')) ?></a>
                <?php if (!empty($profile['full_name'])): ?>
                    <a class="btn btn-ghost" href="<?= e(app_url('/#kontak')) ?>"><?= e(t('offline.contact')) ?></a>
                <?php endif; ?>
            </div>
            <p class="hint mt-3"><?= e(t('offline.tip')) ?></p>
        </div>
    </div>
</section>
