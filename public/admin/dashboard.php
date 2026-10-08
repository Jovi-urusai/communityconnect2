<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Dashboard';

$totalAnnouncements = (int)$pdo->query("SELECT COUNT(*) FROM announcements")->fetchColumn();

$pendingDocRequestsStmt = $pdo->prepare("SELECT COUNT(*) FROM document_requests WHERE status = 'Pending'");
$pendingDocRequestsStmt->execute();
$pendingDocRequests = (int)$pendingDocRequestsStmt->fetchColumn();

$pendingProblemReportsStmt = $pdo->prepare("SELECT COUNT(*) FROM problem_reports WHERE status = 'Pending'");
$pendingProblemReportsStmt->execute();
$pendingProblemReports = (int)$pendingProblemReportsStmt->fetchColumn();

$postsPerCategory = $pdo->query(
    "SELECT category, COUNT(*) AS posts, COALESCE(SUM(views),0) AS total_views
     FROM announcements GROUP BY category ORDER BY posts DESC"
)->fetchAll();

$hour = (int)date('G');
$greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
$todayLabel = date('l, F j, Y');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <div class="dashboard-greeting">
                <h1><?= e($greeting) ?>, <?= e($adminUsername) ?>!</h1>
                <p><?= e($todayLabel) ?></p>
            </div>

            <div class="stat-grid">
                <div class="stat-card stat-card--orange">
                    <div class="stat-card-icon"><?php icon('megaphone'); ?></div>
                    <div class="stat-label">Active Announcements</div>
                    <div class="stat-value"><?= $totalAnnouncements ?></div>
                </div>
                <div class="stat-card stat-card--blue">
                    <div class="stat-card-icon"><?php icon('file-text'); ?></div>
                    <div class="stat-label">Pending Document Requests</div>
                    <div class="stat-value"><?= $pendingDocRequests ?></div>
                </div>
                <div class="stat-card stat-card--red">
                    <div class="stat-card-icon"><?php icon('alert-triangle'); ?></div>
                    <div class="stat-label">Pending Problem Reports</div>
                    <div class="stat-value"><?= $pendingProblemReports ?></div>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <div class="panel-header-title">
                        <div class="panel-header-icon"><?php icon('bar-chart-3'); ?></div>
                        <h3>Posts per Category</h3>
                    </div>
                </div>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Category</th>
                            <th>Posts</th>
                            <th>Total Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($postsPerCategory as $row): ?>
                            <tr>
                                <td><?php render_category_tag($row['category']); ?></td>
                                <td><?= (int)$row['posts'] ?></td>
                                <td><?= number_format((int)$row['total_views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$postsPerCategory): ?>
                            <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
