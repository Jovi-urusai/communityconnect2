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
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <p><a href="lost_found.php" class="text-amber-400 hover:text-amber-300">&larr; Back to Lost Item</a></p>
        <h1 class="mb-1 text-2xl font-bold text-[var(--text)]">Report a Lost Item</h1>
        <p class="mt-0 mb-4 text-sm text-[var(--text-muted)]">Lost something around the barangay? Post it here so other residents can recognize it and let you know.</p>

        <?php if ($success): ?>
            <div class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto">
                <p class="m-0 text-emerald-400 font-semibold">Your lost item report has been posted successfully.</p>
                <p class="text-[var(--text-muted)] text-sm mt-2.5 mb-0">
                    It's now visible to everyone on the <a href="lost_found.php" class="text-amber-400 hover:text-amber-300">Lost Item</a> page, under the "Lost Items" tab.
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
                    <label for="item_name" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Item Name</label>
                    <input type="text" id="item_name" name="item_name" required placeholder="e.g. Black umbrella" value="<?= e($_POST['item_name'] ?? '') ?>"
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-[var(--text-faint)] outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="description" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Description</label>
                    <textarea id="description" name="description" required placeholder="Describe the item, or where you last saw it if you don't have a photo of the item itself."
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-[var(--text-faint)] outline-none focus:border-amber-500 transition-colors min-h-[90px]"><?= e($_POST['description'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="date_lost" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Date Lost</label>
                    <input type="date" id="date_lost" name="date_lost" required value="<?= e($_POST['date_lost'] ?? date('Y-m-d')) ?>"
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="location_lost" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Location Lost</label>
                    <input type="text" id="location_lost" name="location_lost" required placeholder="e.g. Near the covered court, Purok 2" value="<?= e($_POST['location_lost'] ?? '') ?>"
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-[var(--text-faint)] outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="contact_info" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Contact Info (optional)</label>
                    <input type="text" id="contact_info" name="contact_info" placeholder="Phone number, Facebook name, etc. — so a finder can reach you" value="<?= e($_POST['contact_info'] ?? $resident['contact_number'] ?? '') ?>"
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-[var(--text-faint)] outline-none focus:border-amber-500 transition-colors">
                    <p class="field-hint">Optional. Only share what you're comfortable posting publicly.</p>
                </div>
                <div>
                    <label for="photo" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Photos <span class="text-red-400">*</span></label>
                    <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="lostItemPhotoPreview" data-max-files="6"
                        class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg text-sm text-[var(--text)] cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-semibold file:cursor-pointer">
                    <p class="field-hint">Required — a photo of the item, or of where you last saw it. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                    <div id="lostItemPhotoPreview" class="multi-photo-preview"></div>
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">Post Lost Item</button>
            </form>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
