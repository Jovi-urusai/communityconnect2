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

        $stmt = $pdo->prepare("SELECT photo_path FROM problem_reports WHERE id IN ($placeholders)");
        $stmt->execute($ids);
        $photoPaths = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $stmt = $pdo->prepare("SELECT photo_path FROM problem_report_photos WHERE report_id IN ($placeholders)");
        $stmt->execute($ids);
        $photoPaths = array_merge($photoPaths, $stmt->fetchAll(PDO::FETCH_COLUMN));

        $stmt = $pdo->prepare("SELECT photo_path FROM problem_report_after_photos WHERE report_id IN ($placeholders)");
        $stmt->execute($ids);
        $photoPaths = array_merge($photoPaths, $stmt->fetchAll(PDO::FETCH_COLUMN));

        $pdo->prepare("DELETE FROM problem_reports WHERE id IN ($placeholders)")->execute($ids);

        foreach ($photoPaths as $photoPath) {
            if ($photoPath) {
                delete_uploaded_image($photoPath);
            }
        }
    }
}

header('Location: problem_reports.php');
exit;
