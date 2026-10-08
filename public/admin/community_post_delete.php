<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (empty($ids) && !empty($_POST['id'])) {
        $ids = [(int)$_POST['id']];
    }
    $ids = array_values(array_filter($ids, fn($id) => $id > 0));

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $stmt = $pdo->prepare("SELECT photo_path FROM community_post_photos WHERE post_id IN ($placeholders)");
        $stmt->execute($ids);
        $photoPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $pdo->prepare("DELETE FROM community_posts WHERE id IN ($placeholders)")->execute($ids);

        foreach ($photoPaths as $photoPath) {
            if ($photoPath) {
                delete_uploaded_image($photoPath);
            }
        }
    }
}

header('Location: community_posts.php');
exit;
