<?php
/**
 * Admin Page Header Component
 * Hiển thị tiêu đề trang và nút action (nếu có)
 * 
 * @param string $pageTitle - Tiêu đề trang
 * @param string $buttonText - Text của nút (optional)
 * @param string $buttonModal - ID của modal để mở (optional)
 * @param string $buttonHref - Link của nút (optional)
 */

$pageTitle = $pageTitle ?? 'Admin';
$buttonText = $buttonText ?? '';
$buttonModal = $buttonModal ?? '';
$buttonHref = $buttonHref ?? '';
?>

<div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
    <h1 class="h2"><?= htmlspecialchars($pageTitle); ?></h1>
    <?php if ($buttonText): ?>
        <?php if ($buttonModal): ?>
            <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#<?= $buttonModal; ?>">
                <?= $buttonText; ?>
            </button>
        <?php elseif ($buttonHref): ?>
            <a href="<?= $buttonHref; ?>" class="btn btn-primary">
                <?= $buttonText; ?>
            </a>
        <?php endif; ?>
    <?php endif; ?>
</div>
