<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $errors = [];

    if ($id > 0) {
        $stmt = $pdo->prepare("SELECT id FROM problem_reports WHERE id = ?");
        $stmt->execute([$id]);
        if ($stmt->fetch()) {
            $photoPaths = handle_multiple_image_uploads('after_photos', 'problems_after', $errors);

            if (!$photoPaths && !$errors) {
                $errors[] = 'Please choose at least one photo to upload.';
            }

            if (!$errors) {
                $photoStmt = $pdo->prepare("INSERT INTO problem_report_after_photos (report_id, photo_path) VALUES (?, ?)");
                foreach ($photoPaths as $path) {
                    $photoStmt->execute([$id, $path]);
                }
            }
        } else {
            $errors[] = 'Report not found.';
        }
    }

    if ($errors) {
        $_SESSION['after_photo_error'] = implode(' ', $errors);
    }
}

header('Location: problem_reports.php');
exit;
