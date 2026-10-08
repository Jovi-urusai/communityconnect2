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

        $stmt = $pdo->prepare("SELECT id_proof_image FROM document_requests WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $imagePaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT photo_path FROM document_request_photos WHERE request_id IN ($placeholders)");
        $stmt->execute($ids);
        $imagePaths = array_merge($imagePaths, $stmt->fetchAll(PDO::FETCH_COLUMN));

        $pdo->prepare("DELETE FROM document_requests WHERE id IN ($placeholders)")->execute($ids);

        foreach ($imagePaths as $imagePath) {
            if ($imagePath) {
                delete_uploaded_image($imagePath);
            }
        }
    }
}

header('Location: document_requests.php');
exit;
