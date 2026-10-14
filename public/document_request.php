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
    $document_type = trim($_POST['document_type'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $purpose = trim($_POST['purpose'] ?? '');
    $id_proof = trim($_POST['id_proof'] ?? '');

    if ($document_type === '') $errors[] = 'Please select a document type.';
    if ($address === '') $errors[] = 'Address/Purok is required.';
    if ($purpose === '') $errors[] = 'Purpose is required.';

    $idProofImagePaths = handle_multiple_image_uploads('id_proof_images', 'documents', $errors);

    if (!$errors) {
        $referenceNumber = generate_reference_number($pdo);
        $stmt = $pdo->prepare(
            "INSERT INTO document_requests (reference_number, resident_name, document_type, address, purpose, id_proof, status, resident_id)
             VALUES (?, ?, ?, ?, ?, ?, 'Pending', ?)"
        );
        $stmt->execute([$referenceNumber, $resident['full_name'], $document_type, $address, $purpose, $id_proof ?: null, $resident['id']]);
        $requestId = $pdo->lastInsertId();

        $photoStmt = $pdo->prepare("INSERT INTO document_request_photos (request_id, photo_path) VALUES (?, ?)");
        foreach ($idProofImagePaths as $path) {
            $photoStmt->execute([$requestId, $path]);
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
    <title>Request a Document — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <p><a href="index.php" class="text-amber-400 hover:text-amber-300">&larr; Back to announcements</a></p>
        <h1 class="mb-1 text-2xl font-bold text-[var(--text)]">Request a Barangay Document</h1>
        <p class="mt-0 mb-4 text-sm text-[var(--text-muted)]">Fill out the form below. Staff will process your request and notify you for pickup.</p>

        <?php if ($success): ?>
            <div class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto">
                <p class="m-0 mb-4 text-emerald-400 font-semibold">Your request has been submitted successfully.</p>
                <div class="reference-number-box">
                    <button type="button" class="btn btn-sm reference-copy-btn" data-copy-value="<?= e($referenceNumber) ?>">Copy</button>
                    <span class="reference-label">Your Reference Number</span>
                    <span class="reference-value"><?= e($referenceNumber) ?></span>
                </div>
                <div class="reference-warning">
                    <span aria-hidden="true">&#9888;&#65039;</span>
                    <span>Please copy or save this reference number — you'll need it to track your request.</span>
                </div>
                <p class="text-[var(--text-muted)] text-sm mb-0">
                    You can check your status anytime on the <a href="track_request.php" class="text-amber-400 hover:text-amber-300">Track My Request</a> page.
                </p>
            </div>
        <?php else: ?>
            <?php if ($errors): ?>
                <div class="max-w-2xl mx-auto mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 text-sm px-3 py-2"><?= implode('<br>', array_map('e', $errors)) ?></div>
            <?php endif; ?>
            <form method="post" class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto space-y-4" enctype="multipart/form-data">
                <div>
                    <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Requesting As</label>
                    <p class="m-0 font-semibold text-[var(--text)]"><?= e($resident['full_name']) ?> <span class="text-[var(--text-muted)] font-normal">(<?= e($resident['email']) ?>)</span></p>
                </div>
                <div>
                    <label for="document_type" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Document Type</label>
                    <select id="document_type" name="document_type" required
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] outline-none focus:border-amber-500 transition-colors">
                        <option value="">Select document type</option>
                        <option>Barangay Clearance</option>
                        <option>Certificate of Residency</option>
                        <option>Certificate of Indigency</option>
                        <option>Business Permit Endorsement</option>
                        <option>Barangay ID</option>
                    </select>
                </div>
                <div>
                    <label for="address" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Address / Purok</label>
                    <input type="text" id="address" name="address" placeholder="e.g. Purok 3, Sto. Niño" required value="<?= e($_POST['address'] ?? $resident['address'] ?? '') ?>"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-slate-500 outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="purpose" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Purpose</label>
                    <textarea id="purpose" name="purpose" required
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-slate-500 outline-none focus:border-amber-500 transition-colors min-h-[90px]"><?= e($_POST['purpose'] ?? '') ?></textarea>
                </div>
                <div>
                    <label for="id_proof" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">ID Proof (type/number, optional)</label>
                    <input type="text" id="id_proof" name="id_proof" placeholder="e.g. National ID 1234-5678-9012" value="<?= e($_POST['id_proof'] ?? '') ?>"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-slate-500 outline-none focus:border-amber-500 transition-colors">
                </div>
                <div>
                    <label for="id_proof_image" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Attach Photos (ID or proof of address)</label>
                    <input type="file" id="id_proof_image" name="id_proof_images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="idProofEditPreview" data-max-files="6"
                        class="w-full bg-[var(--surface)] border border-[var(--border)] rounded-lg text-sm text-[var(--text)] cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-semibold file:cursor-pointer">
                    <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                    <div id="idProofEditPreview" class="multi-photo-preview"></div>
                </div>
                <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">Submit Request</button>
            </form>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
