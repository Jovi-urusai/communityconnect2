<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $commentId = (int)($_POST['comment_id'] ?? 0);
    $postId = (int)($_POST['post_id'] ?? 0);
    $page = (int)($_POST['page'] ?? 1);

    // Ownership check: a resident may only delete their own comment.
    $stmt = $pdo->prepare("DELETE FROM post_comments WHERE id = ? AND resident_id = ?");
    $stmt->execute([$commentId, $_SESSION['resident_id']]);

    header('Location: community_feed.php?page=' . max(1, $page) . '#post-' . $postId);
    exit;
}

header('Location: community_feed.php');
exit;
