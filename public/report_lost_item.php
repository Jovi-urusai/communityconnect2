<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

$stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->execute([$_SESSION['resident_id']]);
$resident = $stmt->fetch();

$loginError = null;
$success = false;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $item_name = trim($_POST['item_name'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $date_lost = trim($_POST['date_lost'] ?? '');
    $location_lost = trim($_POST['location_lost'] ?? '');
    $contact_info = trim($_POST['contact_info'] ?? '');

    if ($item_name === '') $errors[] = 'Item name is required.';
    if ($description === '') $errors[] = 'Description is required.';
    if ($date_lost === '') $errors[] = 'Date lost is required.';
    if ($location_lost === '') $errors[] = 'Location lost is required.';

    $photoPaths = handle_multiple_image_uploads('photos', 'lost_items', $errors);

    if (!$photoPaths && !$errors) {
        $errors[] = 'At least one photo is required — either of the item itself, or of where you last saw it.';
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO lost_item_reports (item_name, description, date_lost, location_lost, contact_info, resident_id, resident_name)
             VALUES (?, ?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$item_name, $description, $date_lost, $location_lost, $contact_info ?: null, $resident['id'], $resident['full_name']]);
        $reportId = $pdo->lastInsertId();

        $photoStmt = $pdo->prepare("INSERT INTO lost_item_report_photos (report_id, photo_path) VALUES (?, ?)");
        foreach ($photoPaths as $path) {
            $photoStmt->execute([$reportId, $path]);
        }

        $success = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Report a Lost Item — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 560px; padding-top: 24px;">
    <p><a href="lost_found.php">&larr; Back to Lost Item</a></p>
    <h1 style="margin-bottom: 4px;">Report a Lost Item</h1>
    <p style="color: var(--text-muted); margin-top:0;">Lost something around the barangay? Post it here so other residents can recognize it and let you know.</p>

    <?php if ($success): ?>
        <div class="panel" style="padding: 24px;">
            <p style="margin:0; color: var(--success); font-weight:600;">Your lost item report has been posted successfully.</p>
            <p style="color: var(--text-muted); font-size: 13.5px; margin: 10px 0 0;">
                It's now visible to everyone on the <a href="lost_found.php">Lost Item</a> page, under the "Lost Items" tab.
            </p>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" class="panel" style="padding: 20px;" enctype="multipart/form-data">
            <div class="field">
                <label>Reporting As</label>
                <p style="margin:0; font-weight:600;"><?= e($resident['full_name']) ?> <span style="color:var(--text-muted); font-weight:400;">(<?= e($resident['email']) ?>)</span></p>
            </div>
            <div class="field">
                <label for="item_name">Item Name</label>
                <input type="text" id="item_name" name="item_name" required placeholder="e.g. Black umbrella" value="<?= e($_POST['item_name'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="description">Description</label>
                <textarea id="description" name="description" required style="min-height:90px;" placeholder="Describe the item, or where you last saw it if you don't have a photo of the item itself."><?= e($_POST['description'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="date_lost">Date Lost</label>
                <input type="date" id="date_lost" name="date_lost" required value="<?= e($_POST['date_lost'] ?? date('Y-m-d')) ?>">
            </div>
            <div class="field">
                <label for="location_lost">Location Lost</label>
                <input type="text" id="location_lost" name="location_lost" required placeholder="e.g. Near the covered court, Purok 2" value="<?= e($_POST['location_lost'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="contact_info">Contact Info (optional)</label>
                <input type="text" id="contact_info" name="contact_info" placeholder="Phone number, Facebook name, etc. — so a finder can reach you" value="<?= e($_POST['contact_info'] ?? $resident['contact_number'] ?? '') ?>">
                <p class="field-hint">Optional. Only share what you're comfortable posting publicly.</p>
            </div>
            <div class="field">
                <label for="photo">Photos <span style="color:var(--danger);">*</span></label>
                <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="lostItemPhotoPreview" data-max-files="6">
                <p class="field-hint">Required — a photo of the item, or of where you last saw it. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                <div id="lostItemPhotoPreview" class="multi-photo-preview"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Post Lost Item</button>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
