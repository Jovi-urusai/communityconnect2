<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;

$fromLostReportId = 0;
$lostReport = null;
$carryOverPhotos = [];

if (!$editing) {
    $fromLostReportId = (int)($_GET['from_lost_report'] ?? $_POST['from_lost_report_id'] ?? 0);
    if ($fromLostReportId > 0) {
        $stmt = $pdo->prepare("SELECT * FROM lost_item_reports WHERE id = ? AND status = 'Pending'");
        $stmt->execute([$fromLostReportId]);
        $lostReport = $stmt->fetch();
        if ($lostReport) {
            $stmt = $pdo->prepare("SELECT photo_path FROM lost_item_report_photos WHERE report_id = ? ORDER BY id");
            $stmt->execute([$fromLostReportId]);
            $carryOverPhotos = $stmt->fetchAll(PDO::FETCH_COLUMN);
        } else {
            $fromLostReportId = 0;
        }
    }
}

$pageTitle = $editing ? 'Edit Lost Item' : ($fromLostReportId ? 'Mark as Found' : 'New Lost Item');

$data = [
    'item_name' => $lostReport['item_name'] ?? '',
    'description' => $lostReport['description'] ?? '',
    'date_found' => date('Y-m-d'),
    'location_found' => $lostReport['location_lost'] ?? '',
];
$errors = [];
$existingPhotos = [];

if ($editing) {
    $stmt = $pdo->prepare("SELECT * FROM lost_found_items WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: lost_found_items.php');
        exit;
    }
    $data = $existing;

    $stmt = $pdo->prepare("SELECT id, photo_path FROM lost_found_item_photos WHERE item_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $existingPhotos = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['item_name'] = trim($_POST['item_name'] ?? '');
    $data['description'] = trim($_POST['description'] ?? '');
    $data['date_found'] = trim($_POST['date_found'] ?? '');
    $data['location_found'] = trim($_POST['location_found'] ?? '');

    if ($data['item_name'] === '') $errors[] = 'Item name is required.';
    if ($data['description'] === '') $errors[] = 'Description is required.';
    if ($data['date_found'] === '') $errors[] = 'Date found is required.';
    if ($data['location_found'] === '') $errors[] = 'Location found is required.';

    $removePhotoIds = array_map('intval', $_POST['remove_photo_ids'] ?? []);
    $removeCarryoverIdx = array_map('intval', $_POST['remove_carryover_idx'] ?? []);
    $newPhotoPaths = handle_multiple_image_uploads('new_photos', 'lost_found', $errors);

    $remainingExistingCount = count($existingPhotos) - count($removePhotoIds);
    $remainingCarryoverCount = count($carryOverPhotos) - count($removeCarryoverIdx);
    $willHavePhoto = $remainingExistingCount > 0 || $remainingCarryoverCount > 0 || count($newPhotoPaths) > 0;

    if (!$willHavePhoto) {
        $errors[] = 'At least one photo is required for lost items.';
    }

    if (!$errors) {
        if ($editing) {
            $stmt = $pdo->prepare(
                "UPDATE lost_found_items SET item_name=?, description=?, date_found=?, location_found=? WHERE id=?"
            );
            $stmt->execute([$data['item_name'], $data['description'], $data['date_found'], $data['location_found'], $id]);
            $itemId = $id;
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO lost_found_items (item_name, description, date_found, location_found, resolved_lost_report_id, created_at)
                 VALUES (?, ?, ?, ?, ?, NOW())"
            );
            $stmt->execute([$data['item_name'], $data['description'], $data['date_found'], $data['location_found'], $fromLostReportId ?: null]);
            $itemId = $pdo->lastInsertId();
        }

        if ($removePhotoIds) {
            $placeholders = implode(',', array_fill(0, count($removePhotoIds), '?'));
            $stmt = $pdo->prepare("SELECT photo_path FROM lost_found_item_photos WHERE id IN ($placeholders) AND item_id = ?");
            $stmt->execute([...$removePhotoIds, $itemId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $photoPath) {
                delete_uploaded_image($photoPath);
            }
            $pdo->prepare("DELETE FROM lost_found_item_photos WHERE id IN ($placeholders) AND item_id = ?")
                ->execute([...$removePhotoIds, $itemId]);
        }

        $photoStmt = $pdo->prepare("INSERT INTO lost_found_item_photos (item_id, photo_path) VALUES (?, ?)");

        foreach ($carryOverPhotos as $idx => $srcPath) {
            if (in_array($idx, $removeCarryoverIdx, true)) continue;
            $copiedPath = copy_uploaded_image($srcPath, 'lost_found');
            if ($copiedPath) {
                $photoStmt->execute([$itemId, $copiedPath]);
            }
        }

        foreach ($newPhotoPaths as $path) {
            $photoStmt->execute([$itemId, $path]);
        }

        if ($fromLostReportId) {
            $pdo->prepare("UPDATE lost_item_reports SET status = 'Resolved' WHERE id = ?")->execute([$fromLostReportId]);
        }

        header('Location: lost_found_items.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">
            <div class="panel" style="max-width: 640px; padding: 24px;">
                <?php if ($fromLostReportId && $lostReport): ?>
                    <h3 style="margin-top:0;">Mark as Found</h3>
                    <p style="color:var(--text-muted); font-size:13.5px; margin-top:-8px;">
                        Confirming details from a resident's lost item report (submitted <?= date('M j, Y', strtotime($lostReport['created_at'])) ?>).
                        Adjust anything below before publishing it to the public Found Items gallery.
                        <?php if (!empty($lostReport['contact_info'])): ?>
                            <br>Resident's contact info: <strong><?= e($lostReport['contact_info']) ?></strong>
                        <?php endif; ?>
                    </p>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <?php if ($fromLostReportId): ?>
                        <input type="hidden" name="from_lost_report_id" value="<?= (int)$fromLostReportId ?>">
                    <?php endif; ?>
                    <div class="field">
                        <label for="item_name">Item Name</label>
                        <input type="text" id="item_name" name="item_name" required value="<?= e($data['item_name']) ?>">
                    </div>
                    <div class="field">
                        <label for="description">Description</label>
                        <textarea id="description" name="description" required style="min-height:100px;"><?= e($data['description']) ?></textarea>
                    </div>
                    <div class="field">
                        <label for="date_found"><?= $fromLostReportId ? 'Date Actually Found' : 'Date Found' ?></label>
                        <input type="date" id="date_found" name="date_found" required value="<?= e($data['date_found']) ?>">
                    </div>
                    <div class="field">
                        <label for="location_found"><?= $fromLostReportId ? 'Where It Was Actually Found' : 'Location Found' ?></label>
                        <input type="text" id="location_found" name="location_found" required placeholder="e.g. Near the public market" value="<?= e($data['location_found']) ?>">
                        <?php if ($fromLostReportId): ?>
                            <p class="field-hint">Pre-filled with the location the resident said they lost it — adjust to where it was actually found, if different.</p>
                        <?php endif; ?>
                    </div>
                    <div class="field">
                        <label for="photo">Photos <span style="color:var(--danger);">*</span></label>
                        <?php if ($existingPhotos): ?>
                            <div class="multi-photo-preview">
                                <?php foreach ($existingPhotos as $photo): ?>
                                    <div class="photo-thumb">
                                        <img src="../<?= e($photo['photo_path']) ?>" alt="Current photo">
                                        <label class="photo-thumb-remove-label">
                                            <input type="checkbox" name="remove_photo_ids[]" value="<?= (int)$photo['id'] ?>">
                                            <span aria-hidden="true">&times;</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="field-hint">Check a photo to remove it when you save.</p>
                        <?php endif; ?>
                        <?php if ($carryOverPhotos): ?>
                            <p class="field-hint" style="margin-bottom:6px;">Photo(s) from the resident's report — confirm or uncheck to remove:</p>
                            <div class="multi-photo-preview">
                                <?php foreach ($carryOverPhotos as $idx => $photoPath): ?>
                                    <div class="photo-thumb">
                                        <img src="../<?= e($photoPath) ?>" alt="Reported lost item photo">
                                        <label class="photo-thumb-remove-label">
                                            <input type="checkbox" name="remove_carryover_idx[]" value="<?= (int)$idx ?>">
                                            <span aria-hidden="true">&times;</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <input type="file" id="photo" name="new_photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="photoEditPreview" data-max-files="6">
                        <p class="field-hint">At least one photo is required. Add up to 6. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                        <div id="photoEditPreview" class="multi-photo-preview"></div>
                    </div>
                    <div class="modal-actions">
                        <a href="lost_found_items.php" class="btn">Cancel</a>
                        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : ($fromLostReportId ? 'Confirm &amp; Publish as Found' : 'Add Item') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/photo-widgets.js"></script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
