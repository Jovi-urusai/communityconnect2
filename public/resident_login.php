<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';

if (resident_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$notice = $_SESSION['resident_login_notice'] ?? null;
unset($_SESSION['resident_login_notice']);

$successMessage = $_SESSION['resident_register_success'] ?? null;
unset($_SESSION['resident_register_success']);

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');
// Only allow redirecting back to a same-site, known public form — never an
// external URL or an admin page — to avoid this becoming an open redirect.
$allowedRedirects = ['document_request.php', 'report_problem.php', 'report_lost_item.php'];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = '';
}

$errors = [];
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($email === '' || $password === '') {
        $errors[] = 'Please enter both your email and password.';
    } else {
        $stmt = $pdo->prepare("SELECT * FROM residents WHERE email = ?");
        $stmt->execute([$email]);
        $resident = $stmt->fetch();

        if ($resident && password_verify($password, $resident['password_hash'])) {
            $_SESSION['resident_id'] = $resident['id'];
            $_SESSION['resident_name'] = $resident['full_name'];
            $_SESSION['resident_email'] = $resident['email'];

            $pdo->prepare("UPDATE residents SET last_login_at = NOW() WHERE id = ?")->execute([$resident['id']]);

            header('Location: ' . ($redirect !== '' ? $redirect : 'index.php'));
            exit;
        }

        // Deliberately generic — don't reveal whether the email or password was wrong.
        $errors[] = 'Invalid email or password.';
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Resident Login — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 420px; padding-top: 24px;">
    <p><a href="index.php">&larr; Back to announcements</a></p>
    <h1 style="margin-bottom: 4px;">Resident Login</h1>
    <p style="color: var(--text-muted); margin-top:0;">Log in to request documents, report problems, or post a lost item.</p>

    <?php if ($notice): ?>
        <div class="reference-warning">
            <span aria-hidden="true">&#9888;&#65039;</span>
            <span><?= e($notice) ?></span>
        </div>
    <?php endif; ?>
    <?php if ($successMessage): ?>
        <div class="form-error" style="background: rgba(var(--success-rgb), 0.12); border-color: var(--success); color: var(--success);"><?= e($successMessage) ?></div>
    <?php endif; ?>
    <?php if ($errors): ?>
        <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>

    <form method="post" class="panel" style="padding: 20px;" autocomplete="off">
        <?php if ($redirect !== ''): ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
        <?php endif; ?>
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required autocomplete="off">
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Log In</button>
    </form>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const emailInput = document.getElementById('email');
            const passwordInput = document.getElementById('password');
            if (emailInput) emailInput.value = '';
            if (passwordInput) passwordInput.value = '';
        });
    </script>
    <p style="text-align:center; color:var(--text-muted); font-size:13.5px;">
        Don't have an account? <a href="resident_register.php<?= $redirect !== '' ? '?redirect=' . urlencode($redirect) : '' ?>">Sign up</a>
    </p>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
