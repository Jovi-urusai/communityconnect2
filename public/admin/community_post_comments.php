<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Post Comments';

$postId = (int)($_GET['post_id'] ?? 0);

$stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ?");
$stmt->execute([$postId]);
$post = $stmt->fetch();

if (!$post) {
    header('Location: community_posts.php');
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id = ? ORDER BY created_at ASC");
$stmt->execute([$postId]);
$comments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comments on Post #<?= (int)$post['id'] ?> — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <p><a href="community_posts.php">&larr; Back to Community Posts</a></p>

            <div class="panel" style="padding: 20px; margin-bottom: 20px;">
                <p style="margin:0 0 4px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase;">Post by <?= e($post['resident_name']) ?></p>
                <p style="margin:0;"><?= nl2br(e($post['content'])) ?></p>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Comments (<?= count($comments) ?>)</h3>
                </div>
                <?php if (!$comments): ?>
                    <div class="empty-state">No comments on this post yet.</div>
                <?php else: ?>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Commenter</th>
                            <th>Comment</th>
                            <th>Posted</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($comments as $comment): ?>
                            <tr>
                                <td>#<?= (int)$comment['id'] ?></td>
                                <td><?= e($comment['resident_name']) ?></td>
                                <td style="max-width:360px;"><?= e($comment['comment_text']) ?></td>
                                <td><?= date('M j, Y g:i A', strtotime($comment['created_at'])) ?></td>
                                <td class="row-actions">
                                    <form method="post" action="community_comment_delete.php" onsubmit="return confirm('Delete this comment?');">
                                        <input type="hidden" name="id" value="<?= (int)$comment['id'] ?>">
                                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
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
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
