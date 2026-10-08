<?php
require_once __DIR__ . '/includes/admin_layout_top.php';

$id = (int)($_GET['id'] ?? 0);
$editing = $id > 0;
$pageTitle = $editing ? 'Edit Announcement' : 'New Announcement';

$categoryNames = get_category_names($pdo);

$data = [
    'title' => '',
    'category' => $categoryNames[0] ?? '',
    'short_description' => '',
    'content' => '',
    'desk_name' => '',
];
$errors = [];
$newCategoryName = '';
$existingPhotos = [];

if ($editing) {
    $stmt = $pdo->prepare("SELECT * FROM announcements WHERE id = ?");
    $stmt->execute([$id]);
    $existing = $stmt->fetch();
    if (!$existing) {
        header('Location: manage_announcements.php');
        exit;
    }
    $data = $existing;

    $stmt = $pdo->prepare("SELECT id, photo_path FROM announcement_photos WHERE announcement_id = ? ORDER BY id");
    $stmt->execute([$id]);
    $existingPhotos = $stmt->fetchAll();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data['title'] = trim($_POST['title'] ?? '');
    $data['category'] = $_POST['category'] ?? '';
    $data['short_description'] = trim($_POST['short_description'] ?? '');
    $data['content'] = trim($_POST['content'] ?? '');
    $data['desk_name'] = trim($_POST['desk_name'] ?? '') ?: 'Barangay Office';
    $newCategoryName = trim($_POST['new_category_name'] ?? '');
    $isNewCategory = $data['category'] === '__new__';

    if ($isNewCategory && $newCategoryName === '') {
        $errors[] = 'Please enter a name for the new category.';
    }

    if ($data['title'] === '') $errors[] = 'Title is required.';
    if (!$isNewCategory && !in_array($data['category'], $categoryNames, true)) $errors[] = 'Please choose a valid category.';
    if ($data['short_description'] === '') $errors[] = 'Short description is required.';
    if ($data['content'] === '') $errors[] = 'Content is required.';

    $existingMatch = null;
    if ($isNewCategory) {
        foreach ($categoryNames as $name) {
            if (strcasecmp($name, $newCategoryName) === 0) {
                $existingMatch = $name;
                break;
            }
        }
    }
    $resolvedCategoryName = $isNewCategory ? ($existingMatch ?? $newCategoryName) : $data['category'];

    $removePhotoIds = array_map('intval', $_POST['remove_photo_ids'] ?? []);
    $newPhotoPaths = handle_multiple_image_uploads('new_photos', 'announcements', $errors);

    if (!$errors) {
        if ($isNewCategory && !$existingMatch) {
            $newCategoryId = generate_category_id($pdo, $newCategoryName);
            $pdo->prepare("INSERT INTO categories (id, name, color) VALUES (?, ?, ?)")
                ->execute([$newCategoryId, $newCategoryName, DEFAULT_CATEGORY_COLOR]);
        }
        $data['category'] = $resolvedCategoryName;

        if ($editing) {
            $stmt = $pdo->prepare(
                "UPDATE announcements SET title=?, category=?, short_description=?, content=?, desk_name=? WHERE id=?"
            );
            $stmt->execute([$data['title'], $data['category'], $data['short_description'], $data['content'], $data['desk_name'], $id]);
            $announcementId = $id;
        } else {
            $stmt = $pdo->prepare(
                "INSERT INTO announcements (title, category, short_description, content, desk_name, views, created_at)
                 VALUES (?, ?, ?, ?, ?, 0, NOW())"
            );
            $stmt->execute([$data['title'], $data['category'], $data['short_description'], $data['content'], $data['desk_name']]);
            $announcementId = $pdo->lastInsertId();
        }

        if ($removePhotoIds) {
            $placeholders = implode(',', array_fill(0, count($removePhotoIds), '?'));
            $stmt = $pdo->prepare("SELECT photo_path FROM announcement_photos WHERE id IN ($placeholders) AND announcement_id = ?");
            $stmt->execute([...$removePhotoIds, $announcementId]);
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $photoPath) {
                delete_uploaded_image($photoPath);
            }
            $pdo->prepare("DELETE FROM announcement_photos WHERE id IN ($placeholders) AND announcement_id = ?")
                ->execute([...$removePhotoIds, $announcementId]);
        }

        $photoStmt = $pdo->prepare("INSERT INTO announcement_photos (announcement_id, photo_path) VALUES (?, ?)");
        foreach ($newPhotoPaths as $path) {
            $photoStmt->execute([$announcementId, $path]);
        }

        header('Location: manage_announcements.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?> — CommunityConnect Admin</title>
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
                <?php if ($errors): ?>
                    <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
                <?php endif; ?>
                <form method="post" enctype="multipart/form-data">
                    <div class="field">
                        <label for="title">Title</label>
                        <input type="text" id="title" name="title" required value="<?= e($data['title']) ?>">
                    </div>
                    <div class="field">
                        <label for="category">Category</label>
                        <select id="category" name="category" required>
                            <?php foreach ($categoryNames as $cat): ?>
                                <option value="<?= e($cat) ?>" <?= $data['category'] === $cat ? 'selected' : '' ?>><?= e($cat) ?></option>
                            <?php endforeach; ?>
                            <option value="__new__" <?= $data['category'] === '__new__' ? 'selected' : '' ?>>+ Add new category&hellip;</option>
                        </select>
                        <input type="text" id="new_category_name" name="new_category_name" placeholder="New category name"
                               value="<?= e($newCategoryName) ?>"
                               style="margin-top:8px; <?= $data['category'] === '__new__' ? '' : 'display:none;' ?>">
                        <p class="field-hint">Or manage all categories from <a href="manage_categories.php">Manage Categories</a>.</p>
                    </div>
                    <div class="field">
                        <label for="short_description">Short Description</label>
                        <input type="text" id="short_description" name="short_description" required value="<?= e($data['short_description']) ?>">
                    </div>
                    <div class="field">
                        <label for="content">Full Content</label>
                        <textarea id="content" name="content" required style="min-height:140px;"><?= e($data['content']) ?></textarea>
                    </div>
                    <div class="field">
                        <label for="desk_name">Posting Desk</label>
                        <input type="text" id="desk_name" name="desk_name" value="<?= e($data['desk_name']) ?>" placeholder="e.g. Barangay Health Center">
                    </div>
                    <div class="field">
                        <label for="image">Announcement Photos</label>
                        <?php if ($existingPhotos): ?>
                            <div class="multi-photo-preview">
                                <?php foreach ($existingPhotos as $photo): ?>
                                    <div class="photo-thumb">
                                        <img src="../<?= e($photo['photo_path']) ?>" alt="Current photo">
                                        <label class="photo-thumb-remove-label">
                                            <input type="checkbox" name="remove_photo_ids[]" value="<?= (int)$photo['id'] ?>">
                                            <span aria-hidden="true">&times;</span>
                                        </label>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <p class="field-hint">Check a photo to remove it when you save.</p>
                        <?php endif; ?>
                        <input type="file" id="image" name="new_photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="imageEditPreview" data-max-files="6">
                        <p class="field-hint">Add up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                        <div id="imageEditPreview" class="multi-photo-preview"></div>
                    </div>
                    <div class="modal-actions">
                        <a href="manage_announcements.php" class="btn">Cancel</a>
                        <button type="submit" class="btn btn-primary"><?= $editing ? 'Save Changes' : 'Publish Announcement' ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<script src="../assets/js/photo-widgets.js"></script>
<script>
    document.getElementById('category').addEventListener('change', function () {
        var newCategoryInput = document.getElementById('new_category_name');
        var isNew = this.value === '__new__';
        newCategoryInput.style.display = isNew ? 'block' : 'none';
        if (isNew) newCategoryInput.focus();
    });
</script>
<?php include __DIR__ . '/includes/scripts_footer.php'; ?>
</body>
</html>
