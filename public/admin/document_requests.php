<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Document Requests';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    if (in_array($status, DOCUMENT_REQUEST_STATUSES, true)) {
        $pdo->prepare("UPDATE document_requests SET status = ? WHERE id = ?")->execute([$status, $id]);
    }
    header('Location: document_requests.php');
    exit;
}

$requests = $pdo->query("SELECT * FROM document_requests ORDER BY created_at DESC")->fetchAll();

$requestPhotos = [];
if ($requests) {
    $photoStmt = $pdo->query("SELECT request_id, photo_path FROM document_request_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $requestPhotos[$photo['request_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document Requests — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <div class="panel">
                <div class="panel-header">
                    <h3>Resident Document Requests</h3>
                </div>
                <?php if (!$requests): ?>
                    <div class="empty-state">No document requests yet.</div>
                <?php else: ?>
                <div class="bulk-actions" id="bulkBar-requests" data-delete-url="document_request_delete.php" data-item-label="request" hidden>
                    <span class="bulk-count" data-selected-count></span>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                </div>
                <div class="table-scroll">
                <table class="data-table" data-bulk-table="bulkBar-requests">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all requests"></th>
                            <th>ID</th>
                            <th>Reference #</th>
                            <th>Resident</th>
                            <th>Document</th>
                            <th>Address/Purok</th>
                            <th>Purpose</th>
                            <th>ID Proof</th>
                            <th>Photo</th>
                            <th>Status</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $rowNum = 1; ?>
                        <?php foreach ($requests as $r): ?>
                            <tr>
                                <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$r['id'] ?>" aria-label="Select request"></td>
                                <td>#<?= $rowNum++ ?></td>
                                <td><code><?= e($r['reference_number']) ?></code></td>
                                <td><?= e($r['resident_name']) ?></td>
                                <td><?= e($r['document_type']) ?></td>
                                <td><?= e($r['address']) ?></td>
                                <td><?= e($r['purpose']) ?></td>
                                <td><?= e($r['id_proof'] ?: '—') ?></td>
                                <td>
                                    <?php if (!empty($requestPhotos[$r['id']])): ?>
                                        <div class="admin-photo-gallery">
                                            <?php foreach ($requestPhotos[$r['id']] as $photoPath): ?>
                                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="Uploaded photo">
                                                    <img src="../<?= e($photoPath) ?>" alt="Uploaded photo" class="table-thumb">
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" style="display:flex; align-items:center; gap:6px;">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" onchange="window.ccAdminSubmit ? window.ccAdminSubmit(this.form) : this.form.submit()" class="status-pill <?= status_slug($r['status']) ?>" style="border:none; cursor:pointer;">
                                            <?php foreach (DOCUMENT_REQUEST_STATUSES as $s): ?>
                                                <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                                <td class="row-actions">
                                    <form method="post" action="document_request_delete.php" onsubmit="return confirm('Delete this document request?');">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </div>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
<div class="lightbox-overlay" id="lightboxOverlay">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
        <img class="lightbox-image" id="lightboxImage" src="" alt="">
    </div>
</div>
<script src="../assets/js/photo-widgets.js"></script>
<script src="../assets/js/admin.js"></script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
