<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

const COMMENT_MAX_CHARS = 500;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $commentText = trim($_POST['comment_text'] ?? '');
    $page = (int)($_POST['page'] ?? 1);

    $stmt = $pdo->prepare("SELECT id FROM community_posts WHERE id = ?");
    $stmt->execute([$postId]);
    $postExists = (bool)$stmt->fetch();

    if ($postExists && $commentText !== '' && mb_strlen($commentText) <= COMMENT_MAX_CHARS) {
        $stmt = $pdo->prepare("SELECT full_name FROM residents WHERE id = ?");
        $stmt->execute([$_SESSION['resident_id']]);
        $resident = $stmt->fetch();

        $stmt = $pdo->prepare(
            "INSERT INTO post_comments (post_id, resident_id, resident_name, comment_text) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$postId, $_SESSION['resident_id'], $resident['full_name'], $commentText]);
    }
}

header('Location: community_feed.php?page=' . max(1, $page) . '#post-' . $postId);
exit;
