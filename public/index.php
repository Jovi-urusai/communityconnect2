<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$advisoryBanner = get_advisory_banner($pdo);

$coverPhotoSql = "(SELECT photo_path FROM announcement_photos ap WHERE ap.announcement_id = a.id ORDER BY ap.id LIMIT 1) AS cover_photo";

$recommended = $pdo->query(
    "SELECT a.*, $coverPhotoSql FROM announcements a WHERE is_recommended = 1 ORDER BY created_at DESC LIMIT 6"
)->fetchAll();

$trending = $pdo->query(
    "SELECT a.*, $coverPhotoSql FROM announcements a WHERE is_trending = 1 ORDER BY views DESC LIMIT 6"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CommunityConnect — Sto. Niño Barangay Hall, Eustaquio St.</title>
    <style>html, body { background-color: #2b0a10; color: #e8ecf5; }</style>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="public-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="public-content">
        <div class="hero-banner">
            <div class="hero-icon"><?php icon('megaphone'); ?></div>
            <div>
                <h2>Welcome to <span class="brand-accent">CommunityConnect</span>!</h2>
                <p>Stay informed. Report issues. Build a safer and stronger community.</p>
            </div>
        </div>

        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>
        <?php include __DIR__ . '/includes/toolbar.php'; ?>

        <div class="cta-grid">
            <div class="doc-request-cta">
                <div class="cta-info">
                    <div class="cta-icon"><?php icon('file-text'); ?></div>
                    <div>
                        <h3>Need an official document?</h3>
                        <p>Request a barangay clearance, certificate, or ID online and pick it up at the barangay hall.</p>
                    </div>
                </div>
                <a href="document_request.php" class="btn btn-primary">Request a barangay document</a>
            </div>
            <div class="doc-request-cta">
                <div class="cta-info">
                    <div class="cta-icon"><?php icon('alert-triangle'); ?></div>
                    <div>
                        <h3>Noticed a problem nearby?</h3>
                        <p>Report electrical, road, water, sanitation, or safety issues so the barangay can act on them.</p>
                    </div>
                </div>
                <a href="report_problem.php" class="btn btn-primary">Report a Problem</a>
            </div>
        </div>

        <div id="emptyState" class="empty-state" style="display:none;">
            <p>No announcements match your search or filter.</p>
        </div>

        <section class="section">
            <h2 class="section-title"><?php icon('star'); ?> Recommended for you</h2>
            <div class="card-grid">
                <?php foreach ($recommended as $a): render_card($a); endforeach; ?>
            </div>
        </section>

        <section class="section">
            <h2 class="section-title"><?php icon('flame'); ?> Trending this week</h2>
            <div class="card-grid">
                <?php foreach ($trending as $a): render_card($a); endforeach; ?>
            </div>
        </section>

        <div style="text-align:center; margin-bottom: 20px;">
            <a href="category.php" class="btn">Browse all announcements by category &rarr;</a>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
