<?php
require_once __DIR__ . '/../../config/db.php';

function category_slug(string $category): string
{
    $slug = strtolower(trim($category));
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
    return trim($slug, '-');
}

function get_categories(PDO $pdo): array
{
    return $pdo->query("SELECT id, name, color FROM categories ORDER BY name")->fetchAll();
}

function get_category_names(PDO $pdo): array
{
    return array_column(get_categories($pdo), 'name');
}

const DEFAULT_CATEGORY_COLOR = '#2f6fed';

function get_category_color(string $categoryName): string
{
    global $pdo;
    static $map = null;

    if ($map === null) {
        $map = [];
        foreach ($pdo->query("SELECT name, color FROM categories") as $row) {
            $map[$row['name']] = $row['color'];
        }
    }

    return $map[$categoryName] ?? DEFAULT_CATEGORY_COLOR;
}

function render_category_tag(string $categoryName): void
{
    $color = get_category_color($categoryName);
    ?><span class="tag" style="background: <?= e($color) ?>;"><?= e($categoryName) ?></span><?php
}

function generate_category_id(PDO $pdo, string $name): string
{
    $base = category_slug($name);
    if ($base === '') {
        $base = 'category';
    }

    $id = $base;
    $suffix = 2;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE id = ?");

    while (true) {
        $stmt->execute([$id]);
        if (!$stmt->fetchColumn()) {
            break;
        }
        $id = $base . '-' . $suffix;
        $suffix++;
    }

    return $id;
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Renders a Lucide icon placeholder (<i data-lucide="...">), hydrated into an
 * inline SVG in the browser by lucide.createIcons() (see footer.php / the
 * admin scripts_footer.php include).
 */
function icon(string $name, string $class = ''): void
{
    $classAttr = $class !== '' ? ' class="' . e($class) . ' icon"' : ' class="icon"';
    echo '<i data-lucide="' . e($name) . '"' . $classAttr . '></i>';
}

function time_ago(string $datetime): string
{
    $diff = time() - strtotime($datetime);
    if ($diff < 60) {
        return 'Just now';
    }
    $mins = floor($diff / 60);
    if ($mins < 60) {
        return $mins . ' min' . ($mins > 1 ? 's' : '') . ' ago';
    }
    $hours = floor($diff / 3600);
    if ($hours < 24) {
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    }
    $days = floor($diff / 86400);
    if ($days < 1) {
        return 'Today';
    }
    if ($days == 1) {
        return '1 day ago';
    }
    if ($days < 30) {
        return $days . ' days ago';
    }
    $months = floor($days / 30);
    return $months . ' month' . ($months > 1 ? 's' : '') . ' ago';
}

function render_card(array $a): void
{
    $fromPage = basename($_SERVER['SCRIPT_NAME']);
    ?>
    <a class="card" data-card href="announcement.php?id=<?= (int)$a['id'] ?>&amp;from=<?= e($fromPage) ?>"
         data-category="<?= e($a['category']) ?>"
         data-title="<?= e($a['title']) ?>"
         data-content="<?= e($a['short_description'] . ' ' . $a['content']) ?>">
        <?php $coverPhoto = $a['cover_photo'] ?? $a['image_path'] ?? null; ?>
        <?php if (!empty($coverPhoto)): ?>
            <img class="card-image" src="<?= e($coverPhoto) ?>" alt="<?= e($a['title']) ?>">
        <?php endif; ?>
        <?php render_category_tag($a['category']); ?>
        <h4><?= e($a['title']) ?></h4>
        <p class="desc"><?= e($a['short_description']) ?></p>
        <div class="card-meta">
            <span><?= e($a['desk_name']) ?> &middot; <?= time_ago($a['created_at']) ?></span>
            <span class="views"><?php icon('eye'); ?> <?= (int)$a['views'] ?></span>
        </div>
    </a>
    <?php
}

const UPLOAD_ALLOWED_MIME = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/gif' => 'gif',
    'image/webp' => 'webp',
];

const UPLOAD_MAX_BYTES = 5 * 1024 * 1024; // 5MB

/**
 * Handles a single uploaded image file, validating real content type via finfo.
 * Returns the relative web path to store in the DB, or null if no file was
 * provided. Appends a message to $errors and returns null on failure.
 */
function handle_image_upload(string $fieldName, string $subdir, array &$errors): ?string
{
    if (empty($_FILES[$fieldName]) || $_FILES[$fieldName]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    $file = $_FILES[$fieldName];

    if ($file['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'There was a problem uploading the image. Please try again.';
        return null;
    }

    return save_uploaded_image_file($file['tmp_name'], $file['size'], $subdir, $errors);
}

/**
 * Handles multiple uploaded image files from a single <input type="file" multiple>
 * field. Returns an array of relative web paths for the files that were saved
 * successfully (skipping empty slots). Appends a message to $errors for any
 * file that fails validation, without aborting the rest of the batch.
 */
function handle_multiple_image_uploads(string $fieldName, string $subdir, array &$errors, int $maxFiles = 6): array
{
    if (empty($_FILES[$fieldName]) || !is_array($_FILES[$fieldName]['name'] ?? null)) {
        return [];
    }

    $files = $_FILES[$fieldName];
    $count = count($files['name']);
    $paths = [];

    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }

        if (count($paths) >= $maxFiles) {
            $errors[] = "You can upload at most $maxFiles photos.";
            break;
        }

        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            $errors[] = 'There was a problem uploading one of the photos. Please try again.';
            continue;
        }

        $path = save_uploaded_image_file($files['tmp_name'][$i], $files['size'][$i], $subdir, $errors);
        if ($path !== null) {
            $paths[] = $path;
        }
    }

    return $paths;
}

function save_uploaded_image_file(string $tmpName, int $size, string $subdir, array &$errors): ?string
{
    if ($size > UPLOAD_MAX_BYTES) {
        $errors[] = 'One of the images is too large. Maximum size is 5MB each.';
        return null;
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $tmpName);
    finfo_close($finfo);

    if (!isset(UPLOAD_ALLOWED_MIME[$mime])) {
        $errors[] = 'Invalid image type. Allowed formats: JPG, PNG, GIF, WEBP.';
        return null;
    }

    $ext = UPLOAD_ALLOWED_MIME[$mime];
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = __DIR__ . '/../uploads/' . $subdir . '/';
    $destPath = $destDir . $filename;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!move_uploaded_file($tmpName, $destPath)) {
        $errors[] = 'Failed to save one of the uploaded images.';
        return null;
    }

    return 'uploads/' . $subdir . '/' . $filename;
}

function get_advisory_banner(PDO $pdo): ?array
{
    $banner = $pdo->query("SELECT * FROM advisory_banner WHERE id = 1")->fetch();
    return $banner ?: null;
}

/**
 * Physically copies an already-uploaded image (given its relative web path,
 * e.g. "uploads/lost_items/xxx.jpg") into a new subdir under a fresh random
 * filename. Used when carrying a photo over from one record to another
 * (e.g. a resident's lost report becoming a staff-posted found item) so the
 * two records don't end up sharing — and able to independently delete — the
 * same physical file. Returns the new relative path, or null on failure.
 */
function copy_uploaded_image(string $sourceRelativePath, string $subdir): ?string
{
    $sourceFullPath = __DIR__ . '/../' . $sourceRelativePath;
    if (!is_file($sourceFullPath)) {
        return null;
    }

    $ext = strtolower(pathinfo($sourceFullPath, PATHINFO_EXTENSION)) ?: 'jpg';
    $filename = bin2hex(random_bytes(16)) . '.' . $ext;
    $destDir = __DIR__ . '/../uploads/' . $subdir . '/';
    $destPath = $destDir . $filename;

    if (!is_dir($destDir)) {
        mkdir($destDir, 0755, true);
    }

    if (!copy($sourceFullPath, $destPath)) {
        return null;
    }

    return 'uploads/' . $subdir . '/' . $filename;
}

const DOCUMENT_REQUEST_STATUSES = ['Pending', 'Processing', 'Ready for Pickup'];

const PROBLEM_REPORT_STATUSES = ['Pending', 'In Progress', 'Resolved'];

// Facebook-style reaction types for community posts: key => [emoji, label]
const REACTION_TYPES = [
    'Like' => ['emoji' => '👍', 'label' => 'Like'],
    'Love' => ['emoji' => '❤️', 'label' => 'Love'],
    'Haha' => ['emoji' => '😆', 'label' => 'Haha'],
    'Wow' => ['emoji' => '😮', 'label' => 'Wow'],
    'Sad' => ['emoji' => '😢', 'label' => 'Sad'],
    'Angry' => ['emoji' => '😠', 'label' => 'Angry'],
];

function status_slug(string $status): string
{
    return 'status-' . strtolower(str_replace(' ', '-', $status));
}

function generate_reference_number(PDO $pdo, string $table = 'document_requests', string $prefix = 'DR'): string
{
    do {
        $code = $prefix . '-' . strtoupper(bin2hex(random_bytes(4)));
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM $table WHERE reference_number = ?");
        $stmt->execute([$code]);
        $exists = (bool)$stmt->fetchColumn();
    } while ($exists);

    return $code;
}

function delete_uploaded_image(?string $relativePath): void
{
    if (!$relativePath) {
        return;
    }
    $fullPath = __DIR__ . '/../' . $relativePath;
    if (is_file($fullPath)) {
        unlink($fullPath);
    }
}
