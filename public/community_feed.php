<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';
require_resident_login();

$loginError = $_SESSION['login_error'] ?? null;
unset($_SESSION['login_error']);

$baseUrl = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http')
    . '://' . $_SERVER['HTTP_HOST'] . rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/community_feed.php';

$advisoryBanner = get_advisory_banner($pdo);

const POSTS_PER_PAGE = 10;

$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * POSTS_PER_PAGE;

$totalPosts = (int)$pdo->query("SELECT COUNT(*) FROM community_posts")->fetchColumn();
$totalPages = max(1, (int)ceil($totalPosts / POSTS_PER_PAGE));
$page = min($page, $totalPages);
$offset = ($page - 1) * POSTS_PER_PAGE;

$stmt = $pdo->prepare("SELECT * FROM community_posts ORDER BY created_at DESC, id DESC LIMIT :limit OFFSET :offset");
$stmt->bindValue(':limit', POSTS_PER_PAGE, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();
$posts = $stmt->fetchAll();

$postPhotos = [];
$postComments = [];
$reactionCounts = [];
$myReactions = [];

if ($posts) {
    $ids = array_column($posts, 'id');
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $photoStmt = $pdo->prepare("SELECT post_id, photo_path FROM community_post_photos WHERE post_id IN ($placeholders) ORDER BY id");
    $photoStmt->execute($ids);
    foreach ($photoStmt->fetchAll() as $photo) {
        $postPhotos[$photo['post_id']][] = $photo['photo_path'];
    }

    $commentStmt = $pdo->prepare("SELECT * FROM post_comments WHERE post_id IN ($placeholders) ORDER BY created_at ASC, id ASC");
    $commentStmt->execute($ids);
    foreach ($commentStmt->fetchAll() as $comment) {
        $postComments[$comment['post_id']][] = $comment;
    }

    $reactionStmt = $pdo->prepare(
        "SELECT post_id, reaction_type, COUNT(*) AS total FROM post_reactions WHERE post_id IN ($placeholders) GROUP BY post_id, reaction_type"
    );
    $reactionStmt->execute($ids);
    foreach ($reactionStmt->fetchAll() as $row) {
        $reactionCounts[$row['post_id']][$row['reaction_type']] = (int)$row['total'];
    }

    if (resident_is_logged_in()) {
        $myReactionStmt = $pdo->prepare(
            "SELECT post_id, reaction_type FROM post_reactions WHERE post_id IN ($placeholders) AND resident_id = ?"
        );
        $myReactionStmt->execute([...$ids, $_SESSION['resident_id']]);
        foreach ($myReactionStmt->fetchAll() as $row) {
            $myReactions[$row['post_id']] = $row['reaction_type'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Community Feed — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="resident-theme min-h-screen">

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="flex flex-col lg:flex-row items-start gap-0 max-w-[1180px] mx-auto">
    <?php include __DIR__ . '/includes/sidebar.php'; ?>
    <main class="flex-1 min-w-0 px-5 pt-5 pb-5">
        <?php include __DIR__ . '/includes/urgent_banner.php'; ?>

        <div class="flex items-center justify-between flex-wrap gap-3 mb-1">
            <h1 class="m-0 text-xl font-bold text-[var(--text)] flex items-center gap-2"><?php icon('messages-square'); ?> Community Feed</h1>
            <?php if (resident_is_logged_in()): ?>
                <a href="community_post_create.php" class="inline-flex items-center gap-1.5 text-sm bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl shadow-lg transition-all"><?php icon('plus'); ?> Create Post</a>
            <?php else: ?>
                <a href="resident_login.php?redirect=community_feed.php" class="inline-flex items-center gap-1.5 text-sm bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl shadow-lg transition-all"><?php icon('log-in'); ?> Log in to Post</a>
            <?php endif; ?>
        </div>
        <p class="text-[var(--text-muted)] mt-0 mb-4">Anything goes — updates, something for sale, a photo, whatever's on your mind.</p>

        <?php if (!$posts): ?>
            <div class="empty-state">No posts yet. Be the first to share something!</div>
        <?php else: ?>
            <div class="post-feed">
                <?php foreach ($posts as $post): ?>
                    <?php
                        $photos = $postPhotos[$post['id']] ?? [];
                        $comments = $postComments[$post['id']] ?? [];
                        $counts = $reactionCounts[$post['id']] ?? [];
                        $totalReactions = array_sum($counts);
                        $myReaction = $myReactions[$post['id']] ?? null;
                        $isOwner = resident_is_logged_in() && (int)$post['resident_id'] === (int)$_SESSION['resident_id'];
                    ?>
                    <article class="bg-[var(--surface)] backdrop-blur-md border border-[var(--border)] hover:border-[var(--border)] transition-all rounded-2xl shadow-xl p-6 space-y-4" id="post-<?= (int)$post['id'] ?>">
                        <div class="post-card-header">
                            <div class="w-10 h-10 rounded-full bg-amber-500/20 text-amber-400 border border-amber-500/30 flex items-center justify-center font-bold shrink-0"><?= e(mb_strtoupper(mb_substr($post['resident_name'], 0, 1))) ?></div>
                            <div class="post-header-info">
                                <div class="font-bold text-[var(--text)] text-[14.5px]"><?= e($post['resident_name']) ?></div>
                                <div class="post-meta-row">
                                    <span class="text-xs text-[var(--text-muted)]"><?= time_ago($post['created_at']) ?></span>
                                    <span class="post-audience" title="Public"><?php icon('globe'); ?></span>
                                </div>
                            </div>
                            <div class="post-options" data-dropdown>
                                <button type="button" class="post-options-btn" data-dropdown-toggle aria-label="Post options"><?php icon('more-horizontal'); ?></button>
                                <div class="post-options-menu !bg-[var(--bg-elevated)] !border-[var(--border)]" data-dropdown-menu hidden>
                                    <?php if ($isOwner): ?>
                                        <form method="post" action="community_post_delete_own.php" onsubmit="return confirm('Are you sure you want to delete this post?');">
                                            <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                            <button type="submit" class="post-options-item post-options-item-danger"><?php icon('trash-2'); ?> Delete Post</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="post-options-item post-options-item-muted">No actions available</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <p class="text-[var(--text)] text-[14.5px] leading-relaxed whitespace-pre-wrap break-words m-0"><?= nl2br(e($post['content'])) ?></p>
                        <?php if ($photos): ?>
                            <div class="grid gap-2" style="grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));">
                                <?php foreach ($photos as $photo): ?>
                                    <button type="button" class="gallery-photo-trigger block p-0 border-0 bg-none cursor-pointer" data-lightbox-src="<?= e($photo) ?>" data-lightbox-alt="Post photo">
                                        <img src="<?= e($photo) ?>" alt="Post photo" class="w-full max-h-[450px] object-cover rounded-xl border border-[var(--border)]">
                                    </button>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>

                        <?php if ($totalReactions > 0 || $comments): ?>
                            <div class="post-summary-row">
                                <?php if ($totalReactions > 0): ?>
                                    <span class="post-summary-reactions">
                                        <?php foreach ($counts as $type => $count): ?><?= REACTION_TYPES[$type]['emoji'] ?? '' ?><?php endforeach; ?>
                                        <?= $totalReactions ?> reaction<?= $totalReactions === 1 ? '' : 's' ?>
                                    </span>
                                <?php endif; ?>
                                <?php if ($comments): ?>
                                    <span class="post-summary-comments"><?= count($comments) ?> comment<?= count($comments) === 1 ? '' : 's' ?></span>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>

                        <?php
                            // Comment/Share (and the guest Like link) carry no JS hooks on this class, so they
                            // use clean Tailwind utilities. The real Like button must keep the `interaction-btn`
                            // class — photo-widgets.js's long-press handler finds it via wrap.querySelector('.interaction-btn')
                            // — so its color/background utilities are marked `!important` to win over that old rule.
                            $pillBtnClass = 'inline-flex items-center justify-center gap-1.5 flex-1 bg-[var(--surface)] hover:bg-[var(--surface-hover)] text-[var(--text-muted)] hover:text-amber-400 rounded-xl px-4 py-2 text-sm font-medium transition-colors';
                            $likeBtnClass = 'interaction-btn w-full !flex !items-center !justify-center gap-1.5 !bg-[var(--surface)] hover:!bg-[var(--surface-hover)] !text-[var(--text-muted)] hover:!text-amber-400 !rounded-xl !px-4 !py-2 !text-sm !font-medium transition-colors';
                            $likeBtnActiveClass = $likeBtnClass . ' !text-amber-400';
                        ?>
                        <div class="post-interaction-bar flex gap-2 pt-1">
                            <?php if (resident_is_logged_in()): ?>
                                <div class="like-btn-wrap flex-1 relative flex">
                                    <form method="post" action="community_reaction.php" class="flex-1 flex">
                                        <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                        <input type="hidden" name="reaction_type" value="<?= e($myReaction ?: 'Like') ?>">
                                        <input type="hidden" name="page" value="<?= (int)$page ?>">
                                        <button type="submit" class="<?= $myReaction ? $likeBtnActiveClass : $likeBtnClass ?>">
                                            <span class="interaction-btn-emoji"><?= $myReaction ? REACTION_TYPES[$myReaction]['emoji'] : '👍' ?></span>
                                            <span><?= $myReaction ? e(REACTION_TYPES[$myReaction]['label']) : 'Like' ?></span>
                                        </button>
                                    </form>
                                    <div class="reaction-popup !bg-[var(--bg-elevated)] !border-[var(--border)]">
                                        <?php foreach (REACTION_TYPES as $type => $info): ?>
                                            <form method="post" action="community_reaction.php">
                                                <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                                <input type="hidden" name="reaction_type" value="<?= e($type) ?>">
                                                <input type="hidden" name="page" value="<?= (int)$page ?>">
                                                <button type="submit" class="reaction-popup-btn <?= $myReaction === $type ? 'active' : '' ?>" title="<?= e($info['label']) ?>">
                                                    <?= $info['emoji'] ?>
                                                </button>
                                            </form>
                                        <?php endforeach; ?>
                                    </div>
                                </div>
                                <button type="button" class="<?= $pillBtnClass ?>" data-toggle-comments="post-comments-<?= (int)$post['id'] ?>">
                                    <?php icon('message-circle'); ?> <span><?= $comments ? count($comments) . ' Comment' . (count($comments) === 1 ? '' : 's') : 'Comment' ?></span>
                                </button>
                            <?php else: ?>
                                <a href="resident_login.php?redirect=community_feed.php" class="<?= $pillBtnClass ?>">
                                    <span class="interaction-btn-emoji">👍</span> <span>Like</span>
                                </a>
                                <button type="button" class="<?= $pillBtnClass ?>" data-toggle-comments="post-comments-<?= (int)$post['id'] ?>">
                                    <?php icon('message-circle'); ?> <span><?= $comments ? count($comments) . ' Comment' . (count($comments) === 1 ? '' : 's') : 'Comment' ?></span>
                                </button>
                            <?php endif; ?>
                            <button type="button" class="<?= $pillBtnClass ?>" data-copy-value="<?= e($baseUrl . '?page=' . $page . '#post-' . $post['id']) ?>">
                                <?php icon('share-2'); ?> <span>Share</span>
                            </button>
                        </div>

                        <div class="post-comments" id="post-comments-<?= (int)$post['id'] ?>" hidden>
                            <?php foreach ($comments as $comment): ?>
                                <?php $ownsComment = resident_is_logged_in() && (int)$comment['resident_id'] === (int)$_SESSION['resident_id']; ?>
                                <div class="post-comment">
                                    <div class="post-comment-avatar !bg-amber-500/20 !text-amber-400 !border !border-amber-500/30"><?= e(mb_strtoupper(mb_substr($comment['resident_name'], 0, 1))) ?></div>
                                    <div class="post-comment-body">
                                        <div class="post-comment-bubble">
                                            <span class="post-comment-author"><?= e($comment['resident_name']) ?></span>
                                            <?= nl2br(e($comment['comment_text'])) ?>
                                        </div>
                                        <div class="post-comment-meta">
                                            <span><?= time_ago($comment['created_at']) ?></span>
                                            <?php if ($ownsComment): ?>
                                                <form method="post" action="community_comment_delete.php" onsubmit="return confirm('Delete this comment?');" style="display:inline;">
                                                    <input type="hidden" name="comment_id" value="<?= (int)$comment['id'] ?>">
                                                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                                    <input type="hidden" name="page" value="<?= (int)$page ?>">
                                                    <button type="submit" class="post-comment-delete">Delete</button>
                                                </form>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>

                            <?php if (resident_is_logged_in()): ?>
                                <form method="post" action="community_comment_create.php" class="post-comment-form !gap-2">
                                    <input type="hidden" name="post_id" value="<?= (int)$post['id'] ?>">
                                    <input type="hidden" name="page" value="<?= (int)$page ?>">
                                    <input type="text" id="comment-input-post-<?= (int)$post['id'] ?>" name="comment_text" maxlength="500" placeholder="Write a comment..." required
                                        class="!bg-[var(--surface)] !border !border-[var(--border)] !text-[var(--text)] placeholder-slate-500 !rounded-xl focus:!border-amber-500 outline-none transition-colors flex-1 px-3.5 py-2 text-sm">
                                    <button type="submit" class="inline-flex items-center gap-1.5 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold px-4 py-2 rounded-xl text-sm transition-all">Post</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>

            <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?>" class="btn btn-sm"><?php icon('chevron-left'); ?> Newer</a>
                    <?php endif; ?>
                    <span class="pagination-label">Page <?= $page ?> of <?= $totalPages ?></span>
                    <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?>" class="btn btn-sm">Older <?php icon('chevron-right'); ?></a>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>
</div>

<div class="lightbox-overlay" id="lightboxOverlay">
    <div class="lightbox-content">
        <button type="button" class="lightbox-close" id="lightboxClose" aria-label="Close">&times;</button>
        <img class="lightbox-image" id="lightboxImage" src="" alt="">
    </div>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
