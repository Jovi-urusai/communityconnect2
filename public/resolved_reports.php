<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$advisoryBanner = get_advisory_banner($pdo);

$reports = $pdo->query(
    "SELECT * FROM problem_reports WHERE status = 'Resolved' ORDER BY resolved_at DESC, created_at DESC"
)->fetchAll();

$reportPhotos = [];
$afterPhotos = [];
if ($reports) {
    $photoStmt = $pdo->query("SELECT report_id, photo_path FROM problem_report_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $reportPhotos[$photo['report_id']][] = $photo['photo_path'];
    }

    $afterPhotoStmt = $pdo->query("SELECT report_id, photo_path FROM problem_report_after_photos ORDER BY id");
    foreach ($afterPhotoStmt->fetchAll() as $photo) {
        $afterPhotos[$photo['report_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resolved Reports — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="public-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="public-content">
        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>

        <h1 style="margin-bottom: 4px;">Resolved Reports</h1>
        <p style="color: var(--text-muted); margin-top:0;">Problems reported by residents that the barangay has already addressed and fixed.</p>

        <?php if (!$reports): ?>
            <div class="empty-state">No resolved reports yet.</div>
        <?php else: ?>
            <div class="gallery-grid">
                <?php foreach ($reports as $r): ?>
                    <?php
                        $photos = $reportPhotos[$r['id']] ?? [];
                        $coverPhoto = $photos[0] ?? null;
                        $extraPhotos = array_slice($photos, 1);
                        $itemAfterPhotos = $afterPhotos[$r['id']] ?? [];
                    ?>
                    <div class="gallery-card">
                        <?php if ($coverPhoto): ?>
                            <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($coverPhoto) ?>" data-lightbox-alt="<?= e($r['description']) ?>">
                                <img src="<?= e($coverPhoto) ?>" alt="<?= e($r['description']) ?>">
                            </button>
                        <?php else: ?>
                            <div class="gallery-card-placeholder">No Photo</div>
                        <?php endif; ?>
                        <div class="gallery-card-body">
                            <span class="status-pill <?= status_slug($r['status']) ?>"><?php icon('circle-check-big'); ?> Fixed</span>
                            <h4><?= e($r['description']) ?></h4>
                            <div class="card-meta">
                                <span><?php icon('map-pin'); ?> <?= e($r['location']) ?></span>
                                <span><?php icon('calendar'); ?> Resolved <?= date('M j, Y', strtotime($r['resolved_at'] ?? $r['created_at'])) ?></span>
                            </div>
                            <?php if ($extraPhotos): ?>
                                <div class="before-after-block">
                                    <?php if ($itemAfterPhotos): ?><span class="before-after-tag">Before</span><?php endif; ?>
                                    <div class="admin-photo-gallery">
                                        <?php foreach ($extraPhotos as $extraPhoto): ?>
                                            <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($extraPhoto) ?>" data-lightbox-alt="<?= e($r['description']) ?>">
                                                <img src="<?= e($extraPhoto) ?>" alt="<?= e($r['description']) ?>" class="table-thumb">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                            <?php if ($itemAfterPhotos): ?>
                                <div class="before-after-block">
                                    <span class="before-after-tag before-after-tag-after">After</span>
                                    <div class="admin-photo-gallery">
                                        <?php foreach ($itemAfterPhotos as $afterPhoto): ?>
                                            <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($afterPhoto) ?>" data-lightbox-alt="Resolved: <?= e($r['description']) ?>">
                                                <img src="<?= e($afterPhoto) ?>" alt="Resolved: <?= e($r['description']) ?>" class="table-thumb">
                                            </button>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
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
