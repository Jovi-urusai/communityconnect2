<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

const POST_MAX_CHARS = 1000;

$stmt = $pdo->prepare("SELECT * FROM residents WHERE id = ?");
$stmt->execute([$_SESSION['resident_id']]);
$resident = $stmt->fetch();

$loginError = null;
$success = false;
$errors = [];
$content = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $content = trim($_POST['content'] ?? '');

    if ($content === '') {
        $errors[] = 'Please write something to post.';
    } elseif (mb_strlen($content) > POST_MAX_CHARS) {
        $errors[] = 'Your post is too long. Please keep it under ' . POST_MAX_CHARS . ' characters.';
    }

    $photoPaths = handle_multiple_image_uploads('photos', 'community_posts', $errors);

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO community_posts (resident_id, resident_name, content) VALUES (?, ?, ?)"
        );
        $stmt->execute([$resident['id'], $resident['full_name'], $content]);
        $postId = $pdo->lastInsertId();

        $photoStmt = $pdo->prepare("INSERT INTO community_post_photos (post_id, photo_path) VALUES (?, ?)");
        foreach ($photoPaths as $path) {
            $photoStmt->execute([$postId, $path]);
        }

        header('Location: community_feed.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create a Post — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 560px; padding-top: 24px;">
    <p><a href="community_feed.php">&larr; Back to Community Feed</a></p>
    <h1 style="margin-bottom: 4px;">Create a Post</h1>
    <p style="color: var(--text-muted); margin-top:0;">Share anything with your neighbors — updates, something for sale, a photo, whatever's on your mind.</p>

    <?php if ($errors): ?>
        <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" class="panel" style="padding: 20px;" enctype="multipart/form-data">
        <div class="field">
            <label>Posting As</label>
            <p style="margin:0; font-weight:600;"><?= e($resident['full_name']) ?> <span style="color:var(--text-muted); font-weight:400;">(<?= e($resident['email']) ?>)</span></p>
        </div>
        <div class="field">
            <label for="content">What's on your mind?</label>
            <textarea id="content" name="content" required maxlength="<?= POST_MAX_CHARS ?>" style="min-height:120px;" placeholder="Share something with the community..."><?= e($content) ?></textarea>
            <p class="field-hint"><span id="charCount">0</span> / <?= POST_MAX_CHARS ?> characters</p>
        </div>
        <div class="field">
            <label for="photo">Photos (optional)</label>
            <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="postPhotoPreview" data-max-files="6">
            <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
            <div id="postPhotoPreview" class="multi-photo-preview"></div>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Post</button>
    </form>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
    (function () {
        var textarea = document.getElementById('content');
        var counter = document.getElementById('charCount');
        function updateCount() { counter.textContent = textarea.value.length; }
        if (textarea && counter) {
            textarea.addEventListener('input', updateCount);
            updateCount();
        }
    })();
</script>
</body>
</html>
