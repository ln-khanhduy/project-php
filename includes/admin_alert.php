<?php
/**
 * Admin Alert Component
 * Hiển thị thông báo alert
 * 
 * @param string $message - Nội dung thông báo
 * @param string $type - Loại alert (success, danger, warning, info)
 */

if (!empty($message)):
?>
<div class="alert alert-<?= htmlspecialchars($alertType ?? 'success'); ?> alert-dismissible fade show">
    <?= htmlspecialchars($message); ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
</div>
<?php endif; ?>
