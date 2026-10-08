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
$referenceNumber = null;
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $location = trim($_POST['location'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($location === '') $errors[] = 'Location/Purok is required.';
    if ($description === '') $errors[] = 'Description is required.';

    $photoPaths = handle_multiple_image_uploads('photos', 'problems', $errors);

    if (!$errors) {
        $referenceNumber = generate_reference_number($pdo, 'problem_reports', 'PR');
        $stmt = $pdo->prepare(
            "INSERT INTO problem_reports (reference_number, resident_name, location, description, status, resident_id)
             VALUES (?, ?, ?, ?, 'Pending', ?)"
        );
        $stmt->execute([$referenceNumber, $resident['full_name'], $location, $description, $resident['id']]);
        $reportId = $pdo->lastInsertId();

        $photoStmt = $pdo->prepare("INSERT INTO problem_report_photos (report_id, photo_path) VALUES (?, ?)");
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
    <title>Report a Problem — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 560px; padding-top: 24px;">
    <p><a href="index.php">&larr; Back to announcements</a></p>
    <h1 style="margin-bottom: 4px;">Report a Problem</h1>
    <p style="color: var(--text-muted); margin-top:0;">Let the barangay know about an issue in your area. Staff will review and update its status.</p>

    <?php if ($success): ?>
        <div class="panel" style="padding: 24px;">
            <p style="margin:0 0 16px; color: var(--success); font-weight:600;">Your report has been submitted successfully. The barangay office will review it shortly.</p>
            <div class="reference-number-box">
                <span class="reference-label">Your Reference Number</span>
                <span class="reference-value"><?= e($referenceNumber) ?></span>
            </div>
            <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 0;">
                Save or screenshot this number — you'll need it to track your report status.
                You can check your status anytime on the <a href="track_request.php">Track My Request</a> page.
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
                <label for="location">Location / Purok</label>
                <input type="text" id="location" name="location" placeholder="e.g. Purok 4, near the basketball court" required value="<?= e($_POST['location'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="description">Description</label>
                <input type="text" id="description" name="description" required maxlength="150" placeholder="Briefly describe the issue..." value="<?= e($_POST['description'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="photo">Photos of the Problem</label>
                <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="problemPhotoPreview" data-max-files="6">
                <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                <div id="problemPhotoPreview" class="multi-photo-preview"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit Report</button>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
