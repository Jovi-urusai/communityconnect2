<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/resident_auth.php';

if (resident_is_logged_in()) {
    header('Location: index.php');
    exit;
}

$loginError = null;
$errors = [];
$full_name = '';
$email = '';
$contact_number = '';
$address = '';

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');
$allowedRedirects = ['document_request.php', 'report_problem.php', 'report_lost_item.php'];
if (!in_array($redirect, $allowedRedirects, true)) {
    $redirect = '';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $contact_number = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if ($full_name === '') $errors[] = 'Full name is required.';
    if ($email === '') {
        $errors[] = 'Email address is required.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address.';
    }
    if ($password === '') {
        $errors[] = 'Password is required.';
    } elseif (strlen($password) < 8) {
        $errors[] = 'Password must be at least 8 characters long.';
    }
    if ($password !== $confirm_password) {
        $errors[] = 'Passwords do not match.';
    }

    if (!$errors && $email !== '') {
        $stmt = $pdo->prepare("SELECT id FROM residents WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'An account with that email already exists. Try logging in instead.';
        }
    }

    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO residents (full_name, email, password_hash, contact_number, address)
             VALUES (?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $full_name,
            $email,
            password_hash($password, PASSWORD_DEFAULT),
            $contact_number ?: null,
            $address ?: null,
        ]);

        $_SESSION['resident_register_success'] = 'Account created! Please log in to continue.';
        header('Location: resident_login.php' . ($redirect !== '' ? '?redirect=' . urlencode($redirect) : ''));
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create an Account — CommunityConnect</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<main class="container" style="max-width: 480px; padding-top: 24px;">
    <p><a href="index.php">&larr; Back to announcements</a></p>
    <h1 style="margin-bottom: 4px;">Create a Resident Account</h1>
    <p style="color: var(--text-muted); margin-top:0;">Sign up to request documents, report problems, and post lost items.</p>

    <?php if ($errors): ?>
        <div class="form-error"><?= implode('<br>', array_map('e', $errors)) ?></div>
    <?php endif; ?>
    <form method="post" class="panel" style="padding: 20px;">
        <?php if ($redirect !== ''): ?>
            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
        <?php endif; ?>
        <div class="field">
            <label for="full_name">Full Name</label>
            <input type="text" id="full_name" name="full_name" required value="<?= e($full_name) ?>">
        </div>
        <div class="field">
            <label for="email">Email Address</label>
            <input type="email" id="email" name="email" required value="<?= e($email) ?>">
        </div>
        <div class="field">
            <label for="contact_number">Contact Number</label>
            <input type="text" id="contact_number" name="contact_number" placeholder="e.g. 0917-123-4567" value="<?= e($contact_number) ?>">
        </div>
        <div class="field">
            <label for="address">Purok / Address</label>
            <input type="text" id="address" name="address" placeholder="e.g. Purok 3, Sto. Niño" value="<?= e($address) ?>">
            <p class="field-hint">Optional, but helps speed up document requests.</p>
        </div>
        <div class="field">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" required autocomplete="new-password">
            <p class="field-hint">At least 8 characters.</p>
        </div>
        <div class="field">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" required autocomplete="new-password">
        </div>
        <button type="submit" class="btn btn-primary btn-block">Create Account</button>
    </form>
    <p style="text-align:center; color:var(--text-muted); font-size:13.5px;">
        Already have an account? <a href="resident_login.php<?= $redirect !== '' ? '?redirect=' . urlencode($redirect) : '' ?>">Log in</a>
    </p>
</main>

<?php include __DIR__ . '/includes/login_modal.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>
