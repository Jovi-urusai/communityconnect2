<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$advisoryBanner = get_advisory_banner($pdo);

$items = $pdo->query("SELECT * FROM lost_found_items ORDER BY date_found DESC, created_at DESC")->fetchAll();

$itemPhotos = [];
if ($items) {
    $photoStmt = $pdo->query("SELECT item_id, photo_path FROM lost_found_item_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $itemPhotos[$photo['item_id']][] = $photo['photo_path'];
    }
}

$lostReports = $pdo->query("SELECT * FROM lost_item_reports ORDER BY date_lost DESC, created_at DESC")->fetchAll();

$lostReportPhotos = [];
if ($lostReports) {
    $photoStmt = $pdo->query("SELECT report_id, photo_path FROM lost_item_report_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $lostReportPhotos[$photo['report_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Item — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="public-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="public-content">
        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>

        <h1 style="margin-bottom: 4px;">Lost Item</h1>
        <p style="color: var(--text-muted); margin-top:0;">Browse items found around Sto. Niño Barangay Hall, Eustaquio St., or post an item you've lost so residents can help you find it.</p>

        <div class="tab-switcher" data-tabs>
            <div class="tab-switcher-btns">
                <button type="button" class="tab-btn active" data-tab-btn="found"><?php icon('package-search'); ?> Found Items</button>
                <button type="button" class="tab-btn" data-tab-btn="lost"><?php icon('search'); ?> Lost Items</button>
            </div>
            <a href="report_lost_item.php" class="btn btn-primary btn-sm"><?php icon('plus'); ?> Report a Lost Item</a>
        </div>

        <div data-tab-panel="found">
            <?php if (!$items): ?>
                <div class="empty-state">No Found Items posted yet.</div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($items as $item): ?>
                        <?php
                            $photos = $itemPhotos[$item['id']] ?? [];
                            $coverPhoto = $photos[0] ?? null;
                            $extraPhotos = array_slice($photos, 1);
                        ?>
                        <div class="gallery-card">
                            <?php if ($coverPhoto): ?>
                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($coverPhoto) ?>" data-lightbox-alt="<?= e($item['item_name']) ?>">
                                    <img src="<?= e($coverPhoto) ?>" alt="<?= e($item['item_name']) ?>">
                                </button>
                            <?php else: ?>
                                <div class="gallery-card-placeholder">No Photo</div>
                            <?php endif; ?>
                            <div class="gallery-card-body">
                                <span class="status-pill status-ready-for-pickup">Found by Barangay</span>
                                <h4><?= e($item['item_name']) ?></h4>
                                <p class="desc"><?= e($item['description']) ?></p>
                                <div class="card-meta">
                                    <span><?php icon('map-pin'); ?> <?= e($item['location_found']) ?></span>
                                    <span><?php icon('calendar'); ?> <?= date('M j, Y', strtotime($item['date_found'])) ?></span>
                                </div>
                                <?php if ($extraPhotos): ?>
                                    <div class="admin-photo-gallery">
                                        <?php foreach ($extraPhotos as $extraPhoto): ?>
                                            <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($extraPhoto) ?>" data-lightbox-alt="<?= e($item['item_name']) ?>">
                                                <img src="<?= e($extraPhoto) ?>" alt="<?= e($item['item_name']) ?>" class="table-thumb">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div data-tab-panel="lost" hidden>
            <?php if (!$lostReports): ?>
                <div class="empty-state">No Lost Items reported yet. <a href="report_lost_item.php">Report one</a> if something's missing.</div>
            <?php else: ?>
                <div class="gallery-grid">
                    <?php foreach ($lostReports as $report): ?>
                        <?php
                            $photos = $lostReportPhotos[$report['id']] ?? [];
                            $coverPhoto = $photos[0] ?? null;
                            $extraPhotos = array_slice($photos, 1);
                        ?>
                        <div class="gallery-card">
                            <?php if ($coverPhoto): ?>
                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($coverPhoto) ?>" data-lightbox-alt="<?= e($report['item_name']) ?>">
                                    <img src="<?= e($coverPhoto) ?>" alt="<?= e($report['item_name']) ?>">
                                </button>
                            <?php else: ?>
                                <div class="gallery-card-placeholder">No Photo</div>
                            <?php endif; ?>
                            <div class="gallery-card-body">
                                <?php if ($report['status'] === 'Resolved'): ?>
                                    <span class="status-pill status-resolved"><?php icon('circle-check-big'); ?> Found</span>
                                <?php else: ?>
                                    <span class="status-pill status-pending">Reported Lost</span>
                                <?php endif; ?>
                                <h4><?= e($report['item_name']) ?></h4>
                                <p class="desc"><?= e($report['description']) ?></p>
                                <div class="card-meta">
                                    <span><?php icon('map-pin'); ?> <?= e($report['location_lost']) ?></span>
                                    <span><?php icon('calendar'); ?> <?= date('M j, Y', strtotime($report['date_lost'])) ?></span>
                                </div>
                                <?php if (!empty($report['contact_info'])): ?>
                                    <div class="card-meta" style="border-top:none; padding-top:0; margin-top:0;">
                                        <span><?php icon('phone'); ?> <?= e($report['contact_info']) ?></span>
                                    </div>
                                <?php endif; ?>
                                <?php if ($extraPhotos): ?>
                                    <div class="admin-photo-gallery">
                                        <?php foreach ($extraPhotos as $extraPhoto): ?>
                                            <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($extraPhoto) ?>" data-lightbox-alt="<?= e($report['item_name']) ?>">
                                                <img src="<?= e($extraPhoto) ?>" alt="<?= e($report['item_name']) ?>" class="table-thumb">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>
</div>

<div class="lightbox-overlay" id="lightboxOverlay">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
        <img class="lightbox-image" id="lightboxImage" src="" alt="">
    </div>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
