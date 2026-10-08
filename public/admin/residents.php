<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Residents';

$residents = $pdo->query("SELECT * FROM residents ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Residents — CommunityConnect Admin</title>
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
                    <h3>Registered Resident Accounts</h3>
                </div>
                <?php if (!$residents): ?>
                    <div class="empty-state">No resident accounts yet.</div>
                <?php else: ?>
                <div class="bulk-actions" id="bulkBar-residents" data-delete-url="resident_delete.php" data-item-label="account" hidden>
                    <span class="bulk-count" data-selected-count></span>
                    <button type="button" class="btn btn-sm btn-danger" data-bulk-delete-btn>Delete Selected</button>
                </div>
                <div class="table-scroll">
                <table class="data-table" data-bulk-table="bulkBar-residents">
                    <thead>
                        <tr>
                            <th class="select-col"><input type="checkbox" data-select-all aria-label="Select all residents"></th>
                            <th>ID</th>
                            <th>Full Name</th>
                            <th>Email</th>
                            <th>Contact Number</th>
                            <th>Address</th>
                            <th>Date Registered</th>
                            <th>Last Login</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($residents as $resident): ?>
                            <tr>
                                <td class="select-col"><input type="checkbox" class="row-check" value="<?= (int)$resident['id'] ?>" aria-label="Select resident"></td>
                                <td>#<?= (int)$resident['id'] ?></td>
                                <td><?= e($resident['full_name']) ?></td>
                                <td><?= e($resident['email']) ?></td>
                                <td><?= e($resident['contact_number'] ?: '—') ?></td>
                                <td><?= e($resident['address'] ?: '—') ?></td>
                                <td><?= date('M j, Y', strtotime($resident['created_at'])) ?></td>
                                <td><?= $resident['last_login_at'] ? date('M j, Y g:i A', strtotime($resident['last_login_at'])) : 'Never' ?></td>
                                <td class="row-actions">
                                    <form method="post" action="resident_delete.php" onsubmit="return confirm('Are you sure you want to delete this account? This cannot be undone.');">
                                        <input type="hidden" name="id" value="<?= (int)$resident['id'] ?>">
                                        <button type="submit" class="btn btn-sm btn-danger">Delete Account</button>
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
<script src="../assets/js/admin.js"></script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
