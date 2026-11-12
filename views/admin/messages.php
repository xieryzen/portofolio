<?php
/** @var list<array<string, mixed>> $messages */
/** @var int $unread */
?>
<div class="admin-head">
    <div>
        <span class="section-label">Pesan</span>
        <h1 class="mt-1">Pesan masuk</h1>
        <p><?= count($messages) ?> pesan · <?= (int) $unread ?> belum dibaca.</p>
    </div>
</div>

<?php if ($messages === []): ?>
    <div class="card">
        <div class="empty-state">
            <?= svg_icon('message') ?>
            <h3>Belum ada pesan</h3>
            <p>Pesan dari formulir kontak di halaman portofolio akan muncul di sini.</p>
        </div>
    </div>
<?php else: ?>
    <div class="msg-list">
        <?php foreach ($messages as $message): ?>
            <?php $unreadItem = (int) $message['is_read'] === 0; ?>
            <article class="msg-card <?= $unreadItem ? 'is-unread' : '' ?>">
                <button class="msg-head" type="button" data-msg-toggle aria-expanded="false">
                    <span class="msg-avatar"><?= e(initials((string) $message['name'])) ?></span>
                    <span>
                        <strong>
                            <?= e((string) $message['name']) ?>
                            <?php if ($unreadItem): ?><span class="badge badge-accent" style="margin-left:6px">Baru</span><?php endif; ?>
                        </strong>
                        <small><?= e((string) $message['email']) ?> · <?= e(excerpt((string) ($message['subject'] ?: 'Tanpa subjek'), 60)) ?></small>
                    </span>
                    <span class="msg-time"><?= e(time_ago($message['created_at'] ?? null)) ?></span>
                </button>

                <div class="msg-body">
                    <p><?= nl2br(e((string) $message['body'])) ?></p>
                    <div class="msg-actions">
                        <a class="btn btn-soft btn-sm" href="mailto:<?= e((string) $message['email']) ?>?subject=<?= e(rawurlencode('Re: ' . (string) ($message['subject'] ?: 'Pesan dari portofolio'))) ?>">
                            <?= svg_icon('mail', 'icon-sm') ?> Balas via email
                        </a>
                        <form method="post" action="<?= e(app_url('/admin/messages/' . $message['id'] . '/read')) ?>">
                            <?= csrf_field() ?>
                            <button class="btn btn-ghost btn-sm" type="submit">
                                <?= svg_icon($unreadItem ? 'check' : 'eye', 'icon-sm') ?> <?= $unreadItem ? 'Tandai dibaca' : 'Tandai belum dibaca' ?>
                            </button>
                        </form>
                        <form method="post" action="<?= e(app_url('/admin/messages/' . $message['id'] . '/delete')) ?>"
                            data-confirm="Pesan dari &quot;<?= e((string) $message['name']) ?>&quot; akan dihapus permanen." data-confirm-title="Hapus pesan?">
                            <?= csrf_field() ?>
                            <button class="btn btn-danger btn-sm" type="submit"><?= svg_icon('trash', 'icon-sm') ?> Hapus</button>
                        </form>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
<?php endif; ?>
