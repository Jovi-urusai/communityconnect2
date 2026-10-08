<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

// Decision: deleting a resident account does NOT delete their past document
// requests / problem reports / lost item reports. Those tables' resident_id
// column has ON DELETE SET NULL, so the records stay in place for barangay
// history, just no longer linked to a live account. The resident_name (and,
// for document/problem reports, resident_name; for lost items,
// resident_name) captured at submission time is a separate plain-text
// column that is untouched by this delete, so admins still see who
// originally submitted it even after the account is gone.

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ids = array_map('intval', $_POST['ids'] ?? []);
    if (empty($ids) && !empty($_POST['id'])) {
        $ids = [(int)$_POST['id']];
    }
    $ids = array_values(array_filter($ids, fn($id) => $id > 0));

    if ($ids) {
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        $pdo->prepare("DELETE FROM residents WHERE id IN ($placeholders)")->execute($ids);
    }
}

header('Location: residents.php');
exit;
