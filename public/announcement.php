<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

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
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <div class="max-w-[760px] mx-auto bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] rounded-2xl shadow-xl p-6">
            <p><a href="<?= e($from) ?>" class="text-amber-400 hover:text-amber-300">&larr; Back to <?= e($backLabel) ?></a></p>
            <?php render_category_tag($a['category']); ?>
            <h1 class="my-3 text-2xl font-bold text-[var(--text)]"><?= e($a['title']) ?></h1>
            <div class="card-meta" style="border:none; padding:0; margin-bottom: 20px;">
                <span><?= e($a['desk_name']) ?> &middot; <?= time_ago($a['created_at']) ?></span>
                <span class="views"><?php icon('eye'); ?> <?= (int)$a['views'] ?> views</span>
            </div>
            <?php if ($photos): ?>
                <div class="grid gap-3 mb-5" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                    <?php foreach ($photos as $photo): ?>
                        <button type="button" class="gallery-photo-trigger block p-0 border-0 bg-none cursor-pointer" data-lightbox-src="<?= e($photo) ?>" data-lightbox-alt="<?= e($a['title']) ?>">
                            <img src="<?= e($photo) ?>" alt="<?= e($a['title']) ?>" class="w-full aspect-video object-cover rounded-xl border border-[var(--border)]">
                        </button>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <p class="text-[15.5px] text-[var(--text)] leading-relaxed"><?= nl2br(e($a['content'])) ?></p>
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
