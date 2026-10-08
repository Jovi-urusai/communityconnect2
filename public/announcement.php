<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

$backPages = [
    'category.php' => 'Browse by Category',
    'index.php' => 'Home',
];
$from = $_GET['from'] ?? 'index.php';
if (!isset($backPages[$from])) {
    $from = 'index.php';
}
$backLabel = $backPages[$from];

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
$stmt->execute([$id]);
$a = $stmt->fetch();

if (!$a) {
    header('Location: index.php');
    exit;
}

$pdo->prepare("UPDATE announcements SET views = views + 1 WHERE id = ?")->execute([$id]);
$a['views']++;

$photoStmt = $pdo->prepare("SELECT photo_path FROM announcement_photos WHERE announcement_id = ? ORDER BY id");
$photoStmt->execute([$id]);
$photos = $photoStmt->fetchAll(PDO::FETCH_COLUMN);
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($a['title']) ?> — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 760px; padding-top: 24px;">
    <p><a href="<?= e($from) ?>">&larr; Back to <?= e($backLabel) ?></a></p>
    <?php render_category_tag($a['category']); ?>
    <h1 style="margin: 12px 0 4px;"><?= e($a['title']) ?></h1>
    <div class="card-meta" style="border:none; padding:0; margin-bottom: 20px;">
        <span><?= e($a['desk_name']) ?> &middot; <?= time_ago($a['created_at']) ?></span>
        <span class="views"><?php icon('eye'); ?> <?= (int)$a['views'] ?> views</span>
    </div>
    <?php if ($photos): ?>
        <div class="gallery-grid" style="margin-top:0; margin-bottom:20px;">
            <?php foreach ($photos as $photo): ?>
                <button type="button" class="gallery-photo-trigger" data-lightbox-src="<?= e($photo) ?>" data-lightbox-alt="<?= e($a['title']) ?>">
                    <img src="<?= e($photo) ?>" alt="<?= e($a['title']) ?>" style="width:100%; height:220px; object-fit:cover; border-radius: var(--radius);">
                </button>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
    <p style="font-size: 15.5px; color: var(--text);"><?= nl2br(e($a['content'])) ?></p>
</main>

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
