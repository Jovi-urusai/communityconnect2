<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Lost Item';

$items = $pdo->query("SELECT * FROM lost_found_items ORDER BY created_at DESC")->fetchAll();

$itemPhotos = [];
if ($items) {
    $photoStmt = $pdo->query("SELECT item_id, photo_path FROM lost_found_item_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $itemPhotos[$photo['item_id']][] = $photo['photo_path'];
    }
}

$lostReports = $pdo->query("SELECT * FROM lost_item_reports ORDER BY created_at DESC")->fetchAll();

$lostReportPhotos = [];
if ($lostReports) {
    $photoStmt = $pdo->query("SELECT report_id, photo_path FROM lost_item_report_photos ORDER BY id");
    foreach ($photoStmt->fetchAll() as $photo) {
        $lostReportPhotos[$photo['report_id']][] = $photo['photo_path'];
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lost Item — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <div class="tab-switcher" data-tabs>
                <div class="tab-switcher-btns">
                    <button type="button" class="tab-btn active" data-tab-btn="found"><?php icon('package-search'); ?> Found Items (<?= count($items) ?>)</button>
                    <button type="button" class="tab-btn" data-tab-btn="lost"><?php icon('search'); ?> Lost Reports (<?= count($lostReports) ?>)</button>
                </div>
            </div>

            <div data-tab-panel="found">
                <div class="panel">
                    <div class="panel-header">
                        <h3>All Found Items</h3>
                        <a href="lost_found_form.php" class="btn btn-primary">+ New Item</a>
                    </div>
                    <div class="bulk-actions" id="bulkBar-items" data-delete-url="lost_found_delete.php" data-item-label="item" hidden>
                        <span class="bulk-count" data-selected-count></span>
                        <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                    </div>
                    <div class="table-scroll">
                    <table class="data-table" data-bulk-table="bulkBar-items">
                        <thead>
                            <tr>
                                <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all items"></th>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Item Name</th>
                                <th>Date Found</th>
                                <th>Location Found</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNum = 1; ?>
                            <?php foreach ($items as $item): ?>
                                <tr>
                                    <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$item['id'] ?>" aria-label="Select item"></td>
                                    <td>#<?= $rowNum++ ?></td>
                                    <td>
                                        <?php if (!empty($itemPhotos[$item['id']])): ?>
                                            <div class="admin-photo-gallery">
                                                <?php foreach ($itemPhotos[$item['id']] as $photoPath): ?>
                                                    <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="<?= e($item['item_name']) ?>">
                                                        <img src="../<?= e($photoPath) ?>" alt="<?= e($item['item_name']) ?>" class="table-thumb">
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?= e($item['item_name']) ?>
                                        <?php if (!empty($item['resolved_lost_report_id'])): ?>
                                            <br><span class="status-pill status-resolved" style="margin-top:4px;">From lost report #<?= (int)$item['resolved_lost_report_id'] ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= date('M j, Y', strtotime($item['date_found'])) ?></td>
                                    <td><?= e($item['location_found']) ?></td>
                                    <td class="row-actions">
                                        <a href="lost_found_form.php?id=<?= (int)$item['id'] ?>" class="btn btn-sm">Edit</a>
                                        <form method="post" action="lost_found_delete.php" onsubmit="return confirm('Delete this item?');">
                                            <input type="hidden" name="id" value="<?= (int)$item['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$items): ?>
                                <tr><td colspan="7" style="text-align:center; color:var(--text-muted);">No Found Items yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
            </div>

            <div data-tab-panel="lost" hidden>
                <div class="panel">
                    <div class="panel-header">
                        <h3>Resident-Reported Lost Items</h3>
                    </div>
                    <div class="bulk-actions" id="bulkBar-lostReports" data-delete-url="lost_item_report_delete.php" data-item-label="report" hidden>
                        <span class="bulk-count" data-selected-count></span>
                        <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                    </div>
                    <div class="table-scroll">
                    <table class="data-table" data-bulk-table="bulkBar-lostReports">
                        <thead>
                            <tr>
                                <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all lost reports"></th>
                                <th>ID</th>
                                <th>Photo</th>
                                <th>Item Name</th>
                                <th>Description</th>
                                <th>Date Lost</th>
                                <th>Location Lost</th>
                                <th>Contact</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $rowNum = 1; ?>
                            <?php foreach ($lostReports as $report): ?>
                                <tr>
                                    <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$report['id'] ?>" aria-label="Select report"></td>
                                    <td>#<?= $rowNum++ ?></td>
                                    <td>
                                        <?php if (!empty($lostReportPhotos[$report['id']])): ?>
                                            <div class="admin-photo-gallery">
                                                <?php foreach ($lostReportPhotos[$report['id']] as $photoPath): ?>
                                                    <button type="button" class="gallery-photo-trigger" data-lightbox-src="../<?= e($photoPath) ?>" data-lightbox-alt="<?= e($report['item_name']) ?>">
                                                        <img src="../<?= e($photoPath) ?>" alt="<?= e($report['item_name']) ?>" class="table-thumb">
                                                    </button>
                                                <?php endforeach; ?>
                                            </div>
                                        <?php else: ?>
                                            &mdash;
                                        <?php endif; ?>
                                    </td>
                                    <td><?= e($report['item_name']) ?></td>
                                    <td style="max-width:220px;"><?= e($report['description']) ?></td>
                                    <td><?= date('M j, Y', strtotime($report['date_lost'])) ?></td>
                                    <td><?= e($report['location_lost']) ?></td>
                                    <td><?= e($report['contact_info'] ?: '—') ?></td>
                                    <td>
                                        <?php if ($report['status'] === 'Resolved'): ?>
                                            <span class="status-pill status-resolved">Resolved</span>
                                        <?php else: ?>
                                            <span class="status-pill status-pending">Pending</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="row-actions">
                                        <?php if ($report['status'] !== 'Resolved'): ?>
                                            <a href="lost_found_form.php?from_lost_report=<?= (int)$report['id'] ?>" class="btn btn-sm btn-primary">Mark as Found</a>
                                        <?php endif; ?>
                                        <form method="post" action="lost_item_report_delete.php" onsubmit="return confirm('Delete this lost item report? Do this once it has been resolved.');">
                                            <input type="hidden" name="id" value="<?= (int)$report['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$lostReports): ?>
                                <tr><td colspan="10" style="text-align:center; color:var(--text-muted);">No resident-reported lost items yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    </div>
                </div>
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
