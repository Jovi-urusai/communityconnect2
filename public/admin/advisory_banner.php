<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Advisory Banner';

$banner = get_advisory_banner($pdo) ?? [
    'message' => '',
    'severity' => 'red',
    'is_active' => 0,
];

$errors = [];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $banner['message'] = trim($_POST['message'] ?? '');
    $banner['severity'] = $_POST['severity'] ?? 'red';
    $banner['is_active'] = isset($_POST['is_active']) ? 1 : 0;

    if ($banner['message'] === '') $errors[] = 'Banner message is required.';
    if (!in_array($banner['severity'], ['yellow', 'red'], true)) $errors[] = 'Please choose a valid severity.';

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO advisory_banner (id, message, severity, is_active) VALUES (1, ?, ?, ?)
             ON DUPLICATE KEY UPDATE message = VALUES(message), severity = VALUES(severity), is_active = VALUES(is_active)"
        );
        $stmt->execute([$banner['message'], $banner['severity'], $banner['is_active']]);
        $saved = true;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Advisory Banner — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <div class="panel" style="max-width: 640px; padding: 24px;">
                <?php if ($saved): ?>
                    <div class="panel" style="padding: 14px 16px; border-color: var(--success); margin-bottom: 16px;">
                        <p style="margin:0; color: var(--success); font-weight:600;">Advisory banner updated.</p>
                    </div>
                <?php endif; ?>
                <?php if ($errors): ?>
                    <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
                <?php endif; ?>

                <form method="post">
                    <div class="field">
                        <label for="message">Banner Message</label>
                        <textarea id="message" name="message" required style="min-height:100px;"><?= e($banner['message']) ?></textarea>
                        <p class="field-hint">Shown at the top of the homepage and Browse by Category page.</p>
                    </div>
                    <div class="field">
                        <label for="severity">Severity</label>
                        <select id="severity" name="severity" required>
                            <option value="yellow" <?= $banner['severity'] === 'yellow' ? 'selected' : '' ?>>Yellow — Caution / Warning</option>
                            <option value="red" <?= $banner['severity'] === 'red' ? 'selected' : '' ?>>Red — Urgent / Critical</option>
                        </select>
                    </div>
                    <div class="field">
                        <label style="display:flex; align-items:center; gap:8px;">
                            <input type="checkbox" name="is_active" style="width:auto;" <?= !empty($banner['is_active']) ? 'checked' : '' ?>>
                            Show this banner on the public site
                        </label>
                    </div>
                    <div class="modal-actions">
                        <button type="submit" class="btn btn-primary">Save Banner</button>
                    </div>
                </form>
            </div>

            <div class="panel" style="max-width: 640px; padding: 24px;">
                <h3 style="margin-top:0;">Preview</h3>
                <div class="urgent-banner severity-<?= e($banner['severity']) ?>">
                    <div class="urgent-icon">!</div>
                    <div class="urgent-body">
                        <span class="severity-badge"><?= $banner['severity'] === 'red' ? 'URGENT' : 'CAUTION' ?></span>
                        <strong><?= $banner['severity'] === 'red' ? 'Urgent advisory' : 'Advisory' ?>:</strong>
                        <p><?= e($banner['message'] ?: 'Your banner message will appear here.') ?></p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
