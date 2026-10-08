<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$query = trim($_GET['query'] ?? '');
$results = [];
$searched = $query !== '';

if ($searched) {
    $stmt = $pdo->prepare(
        "SELECT * FROM document_requests WHERE reference_number = ? OR resident_name LIKE ? ORDER BY created_at DESC"
    );
    $stmt->execute([$query, '%' . $query . '%']);
    $documentResults = array_map(function ($row) {
        $row['result_type'] = 'document';
        return $row;
    }, $stmt->fetchAll());

    $stmt = $pdo->prepare(
        "SELECT * FROM problem_reports WHERE reference_number = ? OR resident_name LIKE ? ORDER BY created_at DESC"
    );
    $stmt->execute([$query, '%' . $query . '%']);
    $problemResults = array_map(function ($row) {
        $row['result_type'] = 'problem';
        return $row;
    }, $stmt->fetchAll());

    $results = array_merge($documentResults, $problemResults);
    usort($results, fn($a, $b) => strtotime($b['created_at']) <=> strtotime($a['created_at']));
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Track My Request — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="public-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="public-content" style="max-width: 640px;">
        <p><a href="index.php">&larr; Back to announcements</a></p>
        <h1 style="margin-bottom: 4px;">Track My Request</h1>
        <p style="color: var(--text-muted); margin-top:0;">Enter your reference number or full name to check the status of your document request or problem report.</p>

        <form method="get" class="panel" style="padding: 20px; display:flex; gap:10px; align-items:flex-end; flex-wrap:wrap;">
            <div class="field" style="flex:1; min-width:220px; margin-bottom:0;">
                <label for="query">Reference Number or Full Name</label>
                <input type="text" id="query" name="query" placeholder="e.g. DR-9F3A2B7C, PR-1A2B3C4D, or Juan Dela Cruz" value="<?= e($query) ?>" required>
            </div>
            <button type="submit" class="btn btn-primary">Search</button>
        </form>

        <?php if ($searched): ?>
            <?php if (!$results): ?>
                <div class="empty-state">No request or report found matching "<?= e($query) ?>". Please check your reference number or name and try again.</div>
            <?php else: ?>
                <?php foreach ($results as $r): ?>
                    <?php
                        $isProblem = $r['result_type'] === 'problem';
                        $statusSteps = $isProblem ? PROBLEM_REPORT_STATUSES : DOCUMENT_REQUEST_STATUSES;
                        $currentStepIndex = array_search($r['status'], $statusSteps, true);
                    ?>
                    <div class="panel" style="padding: 20px; margin-top: 16px;">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:8px;">
                            <div>
                                <p style="margin:0 0 2px; font-size:12px; color:var(--text-muted); font-weight:600; text-transform:uppercase; letter-spacing:0.04em;"><?= $isProblem ? 'Problem Report' : 'Document Request' ?> Reference Number</p>
                                <p style="margin:0; font-family: 'Courier New', monospace; font-size:18px; font-weight:800; color:var(--accent);"><?= e($r['reference_number']) ?></p>
                            </div>
                            <span class="status-pill <?= status_slug($r['status']) ?>" style="font-size:13px;"><?= e($r['status']) ?></span>
                        </div>

                        <div class="status-tracker">
                            <?php foreach ($statusSteps as $i => $step): ?>
                                <div class="status-step <?= $i <= $currentStepIndex ? 'completed' : '' ?> <?= $i === $currentStepIndex ? 'current' : '' ?>">
                                    <div class="status-step-dot"></div>
                                    <div class="status-step-label"><?= e($step) ?></div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <div class="card-meta" style="border-top:1px solid var(--border); padding-top:12px; margin-top:16px; flex-direction:column; align-items:flex-start; gap:6px;">
                            <span><strong style="color:var(--text);">Resident:</strong> <?= e($r['resident_name']) ?></span>
                            <?php if ($isProblem): ?>
                                <span><strong style="color:var(--text);">Location:</strong> <?= e($r['location']) ?></span>
                                <span><strong style="color:var(--text);">Description:</strong> <?= e($r['description']) ?></span>
                            <?php else: ?>
                                <span><strong style="color:var(--text);">Document:</strong> <?= e($r['document_type']) ?></span>
                            <?php endif; ?>
                            <span><strong style="color:var(--text);">Submitted:</strong> <?= date('M j, Y', strtotime($r['created_at'])) ?> (<?= time_ago($r['created_at']) ?>)</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
