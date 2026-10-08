<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Community Posts';

$posts = $pdo->query("SELECT * FROM community_posts ORDER BY created_at DESC")->fetchAll();

$postPhotos = [];
$commentCounts = [];
if ($posts) {
    $photoStmt = $pdo->query("SELECT post_id, photo_path FROM community_post_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $postPhotos[$photo['post_id']][] = $photo['photo_path'];
    }

    $countStmt = $pdo->query("SELECT post_id, COUNT(*) AS total FROM post_comments GROUP BY post_id");
    foreach ($countStmt->fetchAll() as $row) {
        $commentCounts[$row['post_id']] = (int)$row['total'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Posts — CommunityConnect Admin</title>
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
                    <h3>All Community Posts</h3>
                </div>
                <?php if (!$posts): ?>
                    <div class="empty-state">No community posts yet.</div>
                <?php else: ?>
                <div class="bulk-actions" id="bulkBar-posts" data-delete-url="community_post_delete.php" data-item-label="post" hidden>
                    <span class="bulk-count" data-selected-count></span>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                </div>
                <div class="table-scroll">
                <table class="data-table" data-bulk-table="bulkBar-posts">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all posts"></th>
                            <th>ID</th>
                            <th>Author</th>
                            <th>Text</th>
                            <th>Photo</th>
                            <th>Comments</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($posts as $post): ?>
                            <tr>
                                <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$post['id'] ?>" aria-label="Select post"></td>
                                <td>#<?= (int)$post['id'] ?></td>
                                <td><?= e($post['resident_name']) ?></td>
                                <td style="max-width:280px;"><?= e(mb_strimwidth($post['content'], 0, 120, '…')) ?></td>
                                <td>
                                    <?php if (!empty($postPhotos[$post['id']])): ?>
                                        <div class="admin-photo-gallery">
                                            <?php foreach ($postPhotos[$post['id']] as $photoPath): ?>
                                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="Post photo">
                                                    <img src="../<?= e($photoPath) ?>" alt="Post photo" class="table-thumb">
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="community_post_comments.php?post_id=<?= (int)$post['id'] ?>" class="btn btn-sm">
                                        <?php icon('message-circle'); ?> <?= $commentCounts[$post['id']] ?? 0 ?>
                                    </a>
                                </td>
                                <td><?= date('M j, Y g:i A', strtotime($post['created_at'])) ?></td>
                                <td class="row-actions">
                                    <form method="post" action="community_post_delete.php" onsubmit="return confirm('Delete this post? This cannot be undone.');">
                                        <input type="hidden" name="id" value="<?= (int)$post['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete Post</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
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
