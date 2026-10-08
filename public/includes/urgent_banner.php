<?php if (!empty($advisoryBanner) && $advisoryBanner['is_active']): ?>
<?php $bannerVersion = md5($advisoryBanner['message'] . '|' . $advisoryBanner['severity'] . '|' . $advisoryBanner['updated_at']); ?>
<div class="urgent-banner severity-<?= e($advisoryBanner['severity']) ?>" id="urgentBanner" data-version="<?= e($bannerVersion) ?>">
    <div class="urgent-icon" id="urgentBannerIcon">!</div>
    <div class="urgent-body" id="urgentBannerBody">
        <span class="severity-badge"><?= $advisoryBanner['severity'] === 'red' ? 'URGENT' : 'CAUTION' ?></span>
        <strong><?= $advisoryBanner['severity'] === 'red' ? 'Urgent advisory' : 'Advisory' ?>:</strong>
        <p><?= e($advisoryBanner['message']) ?></p>
    </div>
    <button type="button" class="urgent-close" id="urgentBannerClose" aria-label="Dismiss">&times;</button>
</div>
<?php endif; ?>
