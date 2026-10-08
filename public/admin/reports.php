<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Reports';

$topViewed = $pdo->query("SELECT * FROM announcements ORDER BY views DESC LIMIT 5")->fetchAll();

$byCategory = $pdo->query(
    "SELECT category, COUNT(*) AS posts, COALESCE(SUM(views),0) AS total_views
     FROM announcements GROUP BY category ORDER BY total_views DESC"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports — CommunityConnect Admin</title>
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
                    <h3>Top 5 Most-Viewed Announcements</h3>
                </div>
                <div class="table-scroll">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Title</th>
                            <th>Category</th>
                            <th>Views</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topViewed as $i => $a): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><?= e($a['title']) ?></td>
                                <td><?php render_category_tag($a['category']); ?></td>
                                <td><?= number_format((int)$a['views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$topViewed): ?>
                            <tr><td colspan="4" style="text-align:center; color:var(--text-muted);">No data yet.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                </div>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>Views by Category</h3>
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
                        <?php foreach ($byCategory as $row): ?>
                            <tr>
                                <td><?php render_category_tag($row['category']); ?></td>
                                <td><?= (int)$row['posts'] ?></td>
                                <td><?= number_format((int)$row['total_views']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$byCategory): ?>
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
