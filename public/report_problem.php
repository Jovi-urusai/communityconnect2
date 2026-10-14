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
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <p><a href="index.php" class="text-amber-400 hover:text-amber-300">&larr; Back to announcements</a></p>
        <h1 class="mb-1 text-2xl font-bold text-[var(--text)]">Report a Problem</h1>
        <p class="mt-0 mb-4 text-sm text-[var(--text-muted)]">Let the barangay know about an issue in your area. Staff will review and update its status.</p>

        <?php if ($success): ?>
            <div class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto">
                <p class="m-0 mb-4 text-emerald-400 font-semibold">Your report has been submitted successfully. The barangay office will review it shortly.</p>
                <div class="reference-number-box">
                    <span class="reference-label">Your Reference Number</span>
                    <span class="reference-value"><?= e($referenceNumber) ?></span>
                </div>
                <p class="text-[var(--text-muted)] text-sm mb-0">
                    Save or screenshot this number — you'll need it to track your report status.
                    You can check your status anytime on the <a href="track_request.php" class="text-amber-400 hover:text-amber-300">Track My Request</a> page.
                </p>
            </div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="max-w-2xl mx-auto mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 text-sm px-3 py-2"><?= implode('<br>', array_map('e', $errors)) ?></div>
            <?php endif; ?>
            <form method="post" class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Reporting As</label>
                    <p class="m-0 font-semibold text-[var(--text)]"><?= e($resident['full_name']) ?> <span class="text-[var(--text-muted)] font-normal">(<?= e($resident['email']) ?>)</span></p>
                </div>
                <div>
                    <label for="location" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Location / Purok</label>
                    <input type="text" id="location" name="location" placeholder="e.g. Purok 4, near the basketball court" required value="<?= e($_POST['location'] ?? '') ?>"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-slate-500 outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="description" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Description</label>
                    <input type="text" id="description" name="description" required maxlength="150" placeholder="Briefly describe the issue..." value="<?= e($_POST['description'] ?? '') ?>"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-slate-500 outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="photo" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Photos of the Problem</label>
                    <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="problemPhotoPreview" data-max-files="6"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg text-sm text-[var(--text)] cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-semibold file:cursor-pointer">
                    <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                    <div id="problemPhotoPreview" class="multi-photo-preview"></div>
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">Submit Report</button>
            </form>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
