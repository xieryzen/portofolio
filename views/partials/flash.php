<?php
/** @var list<array{type: string, message: string}> $messages */
$messages = \flash_messages();

$icons = [
    'success' => 'check',
    'error' => 'alert',
    'info' => 'info',
];

if ($messages !== []):
?>
<div class="toast-stack" role="status" aria-live="polite">
    <?php foreach ($messages as $message):
        $type = in_array($message['type'], ['success', 'error', 'info'], true) ? $message['type'] : 'info';
    ?>
    <div class="toast toast-<?= e($type) ?>">
        <?= svg_icon($icons[$type] ?? 'info', 'icon') ?>
        <div><?= e($message['message']) ?></div>
        <button class="toast-close" type="button" aria-label="Tutup notifikasi"><?= svg_icon('close', 'icon-sm') ?></button>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
