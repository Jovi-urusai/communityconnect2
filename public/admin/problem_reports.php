<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Problem Reports';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    if (in_array($status, PROBLEM_REPORT_STATUSES, true)) {
        $pdo->prepare(
            "UPDATE problem_reports
             SET status = ?, resolved_at = IF(? = 'Resolved' AND resolved_at IS NULL, NOW(), resolved_at)
             WHERE id = ?"
        )->execute([$status, $status, $id]);
    }
    header('Location: problem_reports.php');
    exit;
}

$afterPhotoError = $_SESSION['after_photo_error'] ?? null;
unset($_SESSION['after_photo_error']);

$reports = $pdo->query("SELECT * FROM problem_reports ORDER BY created_at DESC")->fetchAll();

$reportPhotos = [];
$afterPhotos = [];
if ($reports) {
    $photoStmt = $pdo->query("SELECT report_id, photo_path FROM problem_report_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $reportPhotos[$photo['report_id']][] = $photo['photo_path'];
    }

    $afterPhotoStmt = $pdo->query("SELECT report_id, photo_path FROM problem_report_after_photos ORDER BY id");
    foreach ($afterPhotoStmt->fetchAll() as $photo) {
        $afterPhotos[$photo['report_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Problem Reports — CommunityConnect Admin</title>
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
                    <h3>Resident Problem Reports</h3>
                </div>
                <?php if ($afterPhotoError): ?>
                    <div class="form-error" style="margin:16px 16px 0;"><?= e($afterPhotoError) ?></div>
                <?php endif; ?>
                <?php if (!$reports): ?>
                    <div class="empty-state">No problem reports yet.</div>
                <?php else: ?>
                <div class="bulk-actions" id="bulkBar-reports" data-delete-url="problem_report_delete.php" data-item-label="report" hidden>
                    <span class="bulk-count" data-selected-count></span>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                </div>
                <div class="table-scroll">
                <table class="data-table" data-bulk-table="bulkBar-reports">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all reports"></th>
                            <th>ID</th>
                            <th>Reference #</th>
                            <th>Resident</th>
                            <th>Location/Purok</th>
                            <th>Description</th>
                            <th>Before</th>
                            <th>After</th>
                            <th>Status</th>
                            <th>Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reports as $r): ?>
                            <tr>
                                <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$r['id'] ?>" aria-label="Select report"></td>
                                <td>#<?= (int)$r['id'] ?></td>
                                <td><code><?= e($r['reference_number']) ?></code></td>
                                <td><?= e($r['resident_name']) ?></td>
                                <td><?= e($r['location']) ?></td>
                                <td style="max-width:260px;"><?= e($r['description']) ?></td>
                                <td>
                                    <?php if (!empty($reportPhotos[$r['id']])): ?>
                                        <div class="admin-photo-gallery">
                                            <?php foreach ($reportPhotos[$r['id']] as $photoPath): ?>
                                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="Reported problem photo">
                                                    <img src="../<?= e($photoPath) ?>" alt="Reported problem photo" class="table-thumb">
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        &mdash;
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($afterPhotos[$r['id']])): ?>
                                        <div class="admin-photo-gallery" style="margin-bottom:6px;">
                                            <?php foreach ($afterPhotos[$r['id']] as $photoPath): ?>
                                                <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="After photo">
                                                    <img src="../<?= e($photoPath) ?>" alt="After photo" class="table-thumb">
                                                </button>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                    <form method="post" action="problem_report_after_photo_upload.php" enctype="multipart/form-data" class="after-photo-upload-form">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="file" name="after_photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp">
                                        <button type="submit" class="btn btn-sm">Upload</button>
                                    </form>
                                </td>
                                <td>
                                    <form method="post" style="display:flex; align-items:center; gap:6px;">
                                        <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                        <input type="hidden" name="update_status" value="1">
                                        <select name="status" onchange="window.ccAdminSubmit ? window.ccAdminSubmit(this.form) : this.form.submit()" class="status-pill <?= status_slug($r['status']) ?>" style="border:none; cursor:pointer;">
                                            <?php foreach (PROBLEM_REPORT_STATUSES as $s): ?>
                                                <option value="<?= $s ?>" <?= $r['status'] === $s ? 'selected' : '' ?>><?= $s ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </form>
                                </td>
                                <td><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
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
