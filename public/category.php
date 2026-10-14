<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$advisoryBanner = get_advisory_banner($pdo);

$announcements = $pdo->query(
    "SELECT a.*, (SELECT photo_path FROM announcement_photos ap WHERE ap.announcement_id = a.id ORDER BY ap.id LIMIT 1) AS cover_photo
     FROM announcements a ORDER BY created_at DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse by Category — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>
        <?php include __DIR__ . '/includes/toolbar.php'; ?>

        <section class="mb-6">
            <h2 class="text-lg font-bold text-[var(--text)] mb-3.5">All Announcements</h2>
            <div id="emptyState" class="empty-state" style="display:none;">
                <p>No announcements match your search or filter.</p>
            </div>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($announcements as $a): render_card($a); endforeach; ?>
            </div>
        </section>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
