<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);
    $reactionType = $_POST['reaction_type'] ?? '';
    $page = (int)($_POST['page'] ?? 1);
    $residentId = $_SESSION['resident_id'];

    if ($postId > 0 && array_key_exists($reactionType, REACTION_TYPES)) {
        $stmt = $pdo->prepare("SELECT reaction_type FROM post_reactions WHERE post_id = ? AND resident_id = ?");
        $stmt->execute([$postId, $residentId]);
        $existing = $stmt->fetchColumn();

        if ($existing === $reactionType) {
            // Clicking the same reaction again removes it.
            $pdo->prepare("DELETE FROM post_reactions WHERE post_id = ? AND resident_id = ?")
                ->execute([$postId, $residentId]);
        } elseif ($existing !== false) {
            // Picking a different reaction replaces the existing one.
            $pdo->prepare("UPDATE post_reactions SET reaction_type = ? WHERE post_id = ? AND resident_id = ?")
                ->execute([$reactionType, $postId, $residentId]);
        } else {
            $pdo->prepare("INSERT INTO post_reactions (post_id, resident_id, reaction_type) VALUES (?, ?, ?)")
                ->execute([$postId, $residentId, $reactionType]);
        }
    }
}

header('Location: community_feed.php?page=' . max(1, $page) . '#post-' . $postId);
exit;
