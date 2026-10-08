<?php
require_once __DIR__ . '/includes/admin_layout_top.php';
$pageTitle = 'Manage Categories';

$flashError = $_SESSION['category_error'] ?? null;
unset($_SESSION['category_error']);

$editId = $_GET['edit'] ?? null;
$editRow = null;
$errors = [];

if ($editId) {
    $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
    $stmt->execute([$editId]);
    $editRow = $stmt->fetch();
    if (!$editRow) {
        header('Location: manage_categories.php');
        exit;
    }
}

function category_name_taken(PDO $pdo, string $name, ?string $excludeId = null): bool
{
    $sql = "SELECT id FROM categories WHERE LOWER(name) = LOWER(?)";
    $params = [$name];
    if ($excludeId !== null) {
        $sql .= " AND id != ?";
        $params[] = $excludeId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return (bool)$stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? DEFAULT_CATEGORY_COLOR);

        if ($name === '') {
            $errors[] = 'Category name is required.';
        } elseif (category_name_taken($pdo, $name)) {
            $errors[] = 'A category with that name already exists.';
        }

        if (!$errors) {
            $newId = generate_category_id($pdo, $name);
            $pdo->prepare("INSERT INTO categories (id, name, color) VALUES (?, ?, ?)")
                ->execute([$newId, $name, $color]);
            header('Location: manage_categories.php');
            exit;
        }
    } elseif ($action === 'edit') {
        $id = $_POST['id'] ?? '';
        $name = trim($_POST['name'] ?? '');
        $color = trim($_POST['color'] ?? DEFAULT_CATEGORY_COLOR);

        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if (!$target) {
            $errors[] = 'Category not found.';
        } elseif ($name === '') {
            $errors[] = 'Category name is required.';
        } elseif (category_name_taken($pdo, $name, $id)) {
            $errors[] = 'A category with that name already exists.';
        }

        if (!$errors) {
            $pdo->beginTransaction();
            $pdo->prepare("UPDATE categories SET name = ?, color = ? WHERE id = ?")->execute([$name, $color, $id]);
            if ($name !== $target['name']) {
                $pdo->prepare("UPDATE announcements SET category = ? WHERE category = ?")->execute([$name, $target['name']]);
            }
            $pdo->commit();
            header('Location: manage_categories.php');
            exit;
        }

        $editRow = ['id' => $id, 'name' => $name, 'color' => $color];
    } elseif ($action === 'delete') {
        $id = $_POST['id'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM categories WHERE id = ?");
        $stmt->execute([$id]);
        $target = $stmt->fetch();

        if ($target) {
            $countStmt = $pdo->prepare("SELECT COUNT(*) FROM announcements WHERE category = ?");
            $countStmt->execute([$target['name']]);
            $usageCount = (int)$countStmt->fetchColumn();

            if ($usageCount > 0) {
                $_SESSION['category_error'] = "Cannot delete \"{$target['name']}\" — it is used by {$usageCount} announcement(s). Reassign or delete those first.";
            } else {
                $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
            }
        }
        header('Location: manage_categories.php');
        exit;
    }
}

$categories = $pdo->query(
    "SELECT c.*, (SELECT COUNT(*) FROM announcements a WHERE a.category = c.name) AS usage_count
     FROM categories c ORDER BY c.name"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Categories — CommunityConnect Admin</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <script>(function(){try{var t=localStorage.getItem("cc_admin_theme");if(t){document.documentElement.setAttribute("data-theme",t);}}catch(e){}})();</script>
</head>
<body>
<div class="admin-shell">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <div class="admin-main">
        <?php include __DIR__ . '/includes/topbar.php'; ?>
        <div class="admin-content">

            <?php if ($flashError): ?>
                <div class="form-error"><?= e($flashError) ?></div>
            <?php endif; ?>

            <div class="panel" style="max-width: 500px; padding: 24px;">
                <h3 style="margin-top:0;"><?= $editRow ? 'Edit Category' : 'Add New Category' ?></h3>
                <?php if ($errors): ?>
                    <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
                <?php endif; ?>
                <form method="post">
                    <input type="hidden" name="action" value="<?= $editRow ? 'edit' : 'add' ?>">
                    <?php if ($editRow): ?>
                        <input type="hidden" name="id" value="<?= e($editRow['id']) ?>">
                    <?php endif; ?>
                    <div class="field">
                        <label for="name">Category Name</label>
                        <input type="text" id="name" name="name" required value="<?= e($editRow['name'] ?? '') ?>">
                    </div>
                    <div class="field">
                        <label for="color">Tag Color</label>
                        <input type="color" id="color" name="color" value="<?= e($editRow['color'] ?? DEFAULT_CATEGORY_COLOR) ?>" style="height:42px; padding:4px; cursor:pointer;">
                    </div>
                    <div class="modal-actions">
                        <?php if ($editRow): ?>
                            <a href="manage_categories.php" class="btn">Cancel</a>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-primary"><?= $editRow ? 'Save Changes' : 'Add Category' ?></button>
                    </div>
                </form>
            </div>

            <div class="panel">
                <div class="panel-header">
                    <h3>All Categories</h3>
                </div>
                <div class="table-scroll">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Category</th>
                                <th>Announcements</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $c): ?>
                                <tr>
                                    <td><span class="tag" style="background: <?= e($c['color']) ?>;"><?= e($c['name']) ?></span></td>
                                    <td><?= (int)$c['usage_count'] ?></td>
                                    <td class="row-actions">
                                        <a href="manage_categories.php?edit=<?= urlencode($c['id']) ?>" class="btn btn-sm">Edit</a>
                                        <form method="post" onsubmit="return confirm('Delete this category?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= e($c['id']) ?>">
                                            <button type="submit" class="btn btn-sm btn-danger" <?= $c['usage_count'] > 0 ? 'title="In use — cannot delete"' : '' ?>>Delete</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (!$categories): ?>
                                <tr><td colspan="3" style="text-align:center; color:var(--text-muted);">No categories yet.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </div>
</div>
<script src="../assets/js/admin.js"></script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
