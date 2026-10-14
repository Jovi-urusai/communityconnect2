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
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <p><a href="community_feed.php" class="text-amber-400 hover:text-amber-300">&larr; Back to Community Feed</a></p>
        <h1 class="mb-1 text-2xl font-bold text-[var(--text)]">Create a Post</h1>
        <p class="mt-0 mb-4 text-sm text-[var(--text-muted)]">Share anything with your neighbors — updates, something for sale, a photo, whatever's on your mind.</p>

        <?php if ($errors): ?>
            <div class="max-w-2xl mx-auto mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 text-sm px-3 py-2"><?= implode('<br>', array_map('e', $errors)) ?></div>
        <?php endif; ?>
        <form method="post" class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] p-8 rounded-2xl shadow-2xl max-w-2xl mx-auto space-y-4" enctype="multipart/form-data">
            <div>
                <label class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Posting As</label>
                <p class="m-0 font-semibold text-[var(--text)]"><?= e($resident['full_name']) ?> <span class="text-[var(--text-muted)] font-normal">(<?= e($resident['email']) ?>)</span></p>
            </div>
            <div>
                <label for="content" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">What's on your mind?</label>
                <textarea id="content" name="content" required maxlength="<?= POST_MAX_CHARS ?>" placeholder="Share something with the community..."
                    class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg px-3.5 py-2.5 text-sm text-[var(--text)] placeholder-[var(--text-faint)] outline-none focus:border-amber-500 transition-colors min-h-[120px]"><?= e($content) ?></textarea>
                <p class="field-hint"><span id="charCount">0</span> / <?= POST_MAX_CHARS ?> characters</p>
            </div>
            <div>
                <label for="photo" class="block text-xs font-semibold text-[var(--text-muted)] mb-1.5">Photos (optional)</label>
                <input type="file" id="photo" name="photos[]" multiple accept="image/jpeg,image/png,image/gif,image/webp" data-multi-photo data-preview-target="postPhotoPreview" data-max-files="6"
                    class="w-full bg-[var(--bg-elevated)] border border-[var(--border)] rounded-lg text-sm text-[var(--text)] cursor-pointer file:mr-3 file:py-2 file:px-3 file:rounded-lg file:border-0 file:bg-amber-500 file:text-slate-950 file:font-semibold file:cursor-pointer">
                <p class="field-hint">Optional. Up to 6 photos. JPG, PNG, GIF, or WEBP. Max 5MB each.</p>
                <div id="postPhotoPreview" class="multi-photo-preview"></div>
            </div>
            <button type="submit" class="w-full inline-flex items-center justify-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2.5 rounded-xl shadow-md transition-all">Post</button>
        </form>
    </main>
</div>

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
