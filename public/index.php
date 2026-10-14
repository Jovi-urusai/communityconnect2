<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

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
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <div class="flex items-center gap-4 bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] rounded-2xl shadow-xl p-6 mb-5">
            <div class="w-14 h-14 rounded-2xl bg-amber-500/15 text-amber-400 flex items-center justify-center shrink-0"><?php icon('megaphone'); ?></div>
            <div>
                <h2 class="m-0 text-xl font-bold text-[var(--text)]">Welcome to <span class="text-amber-400">CommunityConnect</span>!</h2>
                <p class="m-0 text-sm text-[var(--text-muted)]">Stay informed. Report issues. Build a safer and stronger community.</p>
            </div>
        </div>

        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>
        <?php include __DIR__ . '/includes/toolbar.php'; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
            <div class="flex flex-col gap-4 bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] hover:border-amber-500/40 transition-all rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center shrink-0"><?php icon('file-text'); ?></div>
                    <div>
                        <h3 class="m-0 mb-1 text-base font-bold text-[var(--text)]">Need an official document?</h3>
                        <p class="m-0 text-sm text-[var(--text-muted)]">Request a barangay clearance, certificate, or ID online and pick it up at the barangay hall.</p>
                    </div>
                </div>
                <a href="document_request.php" class="self-start inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl shadow-md transition-all">Request a barangay document</a>
            </div>
            <div class="flex flex-col gap-4 bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] hover:border-amber-500/40 transition-all rounded-2xl shadow-xl p-6">
                <div class="flex items-start gap-3">
                    <div class="w-11 h-11 rounded-xl bg-amber-500/15 text-amber-400 flex items-center justify-center shrink-0"><?php icon('alert-triangle'); ?></div>
                    <div>
                        <h3 class="m-0 mb-1 text-base font-bold text-[var(--text)]">Noticed a problem nearby?</h3>
                        <p class="m-0 text-sm text-[var(--text-muted)]">Report electrical, road, water, sanitation, or safety issues so the barangay can act on them.</p>
                    </div>
                </div>
                <a href="report_problem.php" class="self-start inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl shadow-md transition-all">Report a Problem</a>
            </div>
        </div>

        <div id="emptyState" class="empty-state" style="display:none;">
            <p>No announcements match your search or filter.</p>
        </div>

        <section class="mb-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-[var(--text)] mb-3.5"><?php icon('star'); ?> Recommended for you</h2>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($recommended as $a): render_card($a); endforeach; ?>
            </div>
        </section>

        <section class="mb-6">
            <h2 class="flex items-center gap-2 text-lg font-bold text-[var(--text)] mb-3.5"><?php icon('flame'); ?> Trending this week</h2>
            <div class="grid gap-4" style="grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));">
                <?php foreach ($trending as $a): render_card($a); endforeach; ?>
            </div>
        </section>

        <div class="text-center mb-5">
            <a href="category.php" class="inline-flex items-center gap-1.5 text-sm font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--border)] text-[var(--text-muted)] hover:border-amber-500/50 transition-colors">Browse all announcements by category &rarr;</a>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
