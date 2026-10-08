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
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 560px; padding-top: 24px;">
    <p><a href="index.php">&larr; Back to announcements</a></p>
    <h1 style="margin-bottom: 4px;">Request a Barangay Document</h1>
    <p style="color: var(--text-muted); margin-top:0;">Fill out the form below. Staff will process your request and notify you for pickup.</p>

    <?php if ($success): ?>
        <div class="panel" style="padding: 24px;">
            <p style="margin:0 0 16px; color: var(--success); font-weight:600;">Your request has been submitted successfully.</p>
            <div class="reference-number-box">
                <button type="button" class="btn btn-sm reference-copy-btn" data-copy-value="<?= e($referenceNumber) ?>">Copy</button>
                <span class="reference-label">Your Reference Number</span>
                <span class="reference-value"><?= e($referenceNumber) ?></span>
            </div>
            <div class="reference-warning">
                <span aria-hidden="true">&#9888;&#65039;</span>
                <span>Please copy or save this reference number — you'll need it to track your request.</span>
            </div>
            <p style="color: var(--text-muted); font-size: 13.5px; margin-bottom: 0;">
                You can check your status anytime on the <a href="track_request.php">Track My Request</a> page.
            </p>
        </div>
    <?php else: ?>
        <?php if ($errors): ?>
            <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" class="panel" style="padding: 20px;" enctype="multipart/form-data">
            <div class="field">
                <label>Requesting As</label>
                <p style="margin:0; font-weight:600;"><?= e($resident['full_name']) ?> <span style="color:var(--text-muted); font-weight:400;">(<?= e($resident['email']) ?>)</span></p>
            </div>
            <div class="field">
                <label for="document_type">Document Type</label>
                <select id="document_type" name="document_type" required>
                    <option value="">Select document type</option>
                    <option>Barangay Clearance</option>
                    <option>Certificate of Residency</option>
                    <option>Certificate of Indigency</option>
                    <option>Business Permit Endorsement</option>
                    <option>Barangay ID</option>
                </select>
            </div>
            <div class="field">
                <label for="address">Address / Purok</label>
                <input type="text" id="address" name="address" placeholder="e.g. Purok 3, Sto. Niño" required value="<?= e($_POST['address'] ?? $resident['address'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="purpose">Purpose</label>
                <textarea id="purpose" name="purpose" required><?= e($_POST['purpose'] ?? '') ?></textarea>
            </div>
            <div class="field">
                <label for="id_proof">ID Proof (type/number, optional)</label>
                <input type="text" id="id_proof" name="id_proof" placeholder="e.g. National ID 1234-5678-9012" value="<?= e($_POST['id_proof'] ?? '') ?>">
            </div>
            <div class="field">
                <label for="id_proof_image">Attach Photos (ID or proof of address)</label>
                <input type="file" id="id_proof_image" name="id_proof_images[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="idProofEditPreview" data-max-files="6">
                <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                <div id="idProofEditPreview" class="multi-photo-preview"></div>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Submit Request</button>
        </form>
    <?php endif; ?>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
