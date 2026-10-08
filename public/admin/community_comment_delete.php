<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($id > 0) {
        $pdo->prepare("DELETE FROM post_comments WHERE id = ?")->execute([$id]);
    }

    header('Location: community_post_comments.php?post_id=' . $postId);
    exit;
}

header('Location: community_posts.php');
exit;
