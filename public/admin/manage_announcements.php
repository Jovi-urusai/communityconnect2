<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Manage Announcements';

$announcements = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();

$announcementPhotos = [];
if ($announcements) {
    $photoStmt = $pdo->query("SELECT announcement_id, photo_path FROM announcement_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $announcementPhotos[$photo['announcement_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Announcements — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <div class="panel">
                <div class="panel-header">
                    <h3>All Announcements</h3>
                    <a href="announcement_form.php" class="btn btn-primary">+ New Announcement</a>
                </div>
                <div class="bulk-actions" id="bulkBar-announcements" data-delete-url="delete_announcement.php" data-item-label="announcement" hidden>
                    <span class="bulk-count" data-selected-count></span>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                </div>
                <div class="table-scroll">
                <table class="data-table" data-bulk-table="bulkBar-announcements">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all announcements"></th>
                            <th>ID</th>
                            <th>Photo</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Date</th>
                            <th>Views</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNum = 1; ?>
                        <?php foreach ($announcements as $a): ?>
                            <tr>
                                <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$a['id'] ?>" aria-label="Select announcement"></td>
                                <td>#<?= $rowNum++ ?></td>
                                <td>
                                    <?php if (!empty($announcementPhotos[$a['id']])): ?>
                                        <div class="admin-photo-gallery">
                                            <?php foreach ($announcementPhotos[$a['id']] as $photoPath): ?>
                                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="<?= e($a['title']) ?>">
                                                    <img src="../<?= e($photoPath) ?>" alt="<?= e($a['title']) ?>" class="table-thumb">
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td><?= e($a['title']) ?></td>
                                <td><?php render_category_tag($a['category']); ?></td>
                                <td><?= date('M j, Y', strtotime($a['created_at'])) ?></td>
                                <td><?= (int)$a['views'] ?></td>
                                <td class="row-actions">
                                    <a href="announcement_form.php?id=<?= (int)$a['id'] ?>" class="btn btn-sm">Edit</a>
                                    <form method="post" action="delete_announcement.php" onsubmit="return confirm('Delete this announcement?');">
                                        <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$announcements): ?>
                            <tr><td colspan="8" style="text-align:center; color:var(--text-muted);">No announcements yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</div>
<div class="lightbox-overlay" id="lightboxOverlay">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
        <img class="lightbox-image" id="lightboxImage" src="" alt="">
    </div>
</div>
<script src="../assets/js/photo-widgets.js"></script>
<script src="../assets/js/admin.js"></script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
