<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postId = (int)($_POST['post_id'] ?? 0);

    // Ownership check: a resident may only delete a post that is actually
    // theirs (post_id + resident_id must match the logged-in session).
    $stmt = $pdo->prepare("SELECT * FROM community_posts WHERE id = ? AND resident_id = ?");
    $stmt->execute([$postId, $_SESSION['resident_id']]);
    $post = $stmt->fetch();

    if ($post) {
        $stmt = $pdo->prepare("SELECT photo_path FROM community_post_photos WHERE post_id = ?");
        $stmt->execute([$postId]);
        $photoPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // ON DELETE CASCADE on community_post_photos, post_comments, and
        // post_reactions takes care of removing everything tied to this
        // post — nothing orphaned is left behind.
        $pdo->prepare("DELETE FROM community_posts WHERE id = ?")->execute([$postId]);

        foreach ($photoPaths as $photoPath) {
            if ($photoPath) {
                delete_uploaded_image($photoPath);
            }
        }
    }
}

header('Location: community_feed.php');
exit;
