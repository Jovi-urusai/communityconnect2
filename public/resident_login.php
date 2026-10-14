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
$allowedRedirects = [
    'document_request.php', 'report_problem.php', 'report_lost_item.php',
    'index.php', 'category.php', 'announcement.php', 'community_feed.php',
    'lost_found.php', 'resolved_reports.php', 'track_request.php',
];
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
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="min-h-screen bg-slate-950 text-white bg-cover bg-center bg-no-repeat bg-fixed" style="background-image: url('assets/images/barangay-hall-bg.png');">

<div class="fixed inset-0 bg-slate-950/80 backdrop-blur-sm"></div>

<div class="relative min-h-screen flex flex-col">

    <header class="relative z-10 flex items-center justify-between px-6 md:px-10 py-5">
        <a href="index.php" class="flex items-center gap-3">
            <div class="w-11 h-11 rounded-xl bg-amber-500 flex items-center justify-center text-slate-900 shrink-0">
                <?php icon('landmark'); ?>
            </div>
            <div>
                <div class="font-bold text-lg leading-tight">Community<span class="text-amber-400">Connect</span></div>
                <div class="text-xs text-slate-400 leading-tight">Barangay Sto. Niño Official Portal</div>
            </div>
        </a>
        <button type="button" id="openLoginModal" class="flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-full border border-slate-700 bg-slate-900/60 hover:border-amber-500/60 hover:text-amber-400 transition-colors">
            <?php icon('shield'); ?> Staff / Admin Access
        </button>
    </header>

    <main class="relative z-10 flex-1 flex items-center justify-center p-4 md:p-8">
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-10 max-w-6xl w-full items-center">

            <div>
                <div class="flex flex-wrap items-center gap-3 mb-6">
                    <span class="inline-flex items-center gap-2 text-xs font-bold tracking-wide px-4 py-2 rounded-full border border-slate-700 bg-slate-900/60">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        STO. NIÑO DIGITAL HUB
                    </span>
                    <span class="inline-flex items-center gap-2 text-xs font-semibold px-4 py-2 rounded-full border border-slate-700 bg-slate-900/60 text-slate-300" id="authClock">
                        <?php icon('clock'); ?> --:--:-- --
                    </span>
                </div>

                <h1 class="text-4xl md:text-5xl font-extrabold leading-tight mb-4">
                    WELCOME TO<br>
                    <span class="text-amber-400">BARANGAY STO. NIÑO</span>
                </h1>
                <p class="text-slate-300 max-w-lg mb-8">
                    Experience seamless, 24/7 digital public service. Submit document requests, report community issues, and stay connected with our central portal.
                </p>

                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="border border-slate-700/60 bg-slate-900/60 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center mb-3">
                            <?php icon('zap'); ?>
                        </div>
                        <div class="font-bold text-sm mb-1">Instant Portal</div>
                        <div class="text-xs text-slate-400">100% online process without long queues.</div>
                    </div>
                    <div class="border border-slate-700/60 bg-slate-900/60 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center mb-3">
                            <?php icon('file-text'); ?>
                        </div>
                        <div class="font-bold text-sm mb-1">Clearances &amp; IDs</div>
                        <div class="text-xs text-slate-400">Request documents anytime, anywhere.</div>
                    </div>
                    <div class="border border-slate-700/60 bg-slate-900/60 rounded-xl p-4">
                        <div class="w-9 h-9 rounded-lg bg-amber-500/15 text-amber-400 flex items-center justify-center mb-3">
                            <?php icon('megaphone'); ?>
                        </div>
                        <div class="font-bold text-sm mb-1">Incident Reports</div>
                        <div class="text-xs text-slate-400">Direct escalation to Barangay Officers.</div>
                    </div>
                </div>
            </div>

            <div class="bg-slate-900/70 backdrop-blur-md border border-slate-700/50 p-8 rounded-2xl shadow-2xl w-full max-w-md mx-auto lg:mx-0 lg:ml-auto">
                <div class="flex gap-2 mb-6 bg-slate-950/50 p-1.5 rounded-xl">
                    <button type="button" id="tabBtnLogin" class="flex-1 flex items-center justify-center gap-1.5 text-sm font-semibold py-2.5 rounded-lg transition-colors">
                        <?php icon('log-in'); ?> Resident Login
                    </button>
                    <button type="button" id="tabBtnSignup" class="flex-1 flex items-center justify-center gap-1.5 text-sm font-semibold py-2.5 rounded-lg transition-colors">
                        <?php icon('user-plus'); ?> Sign Up
                    </button>
                </div>

                <div id="panelLogin">
                    <h2 class="text-xl font-bold mb-1">Welcome Back</h2>
                    <p class="text-sm text-slate-400 mb-5">Please enter your verified resident credentials.</p>

                    <?php if ($notice): ?>
                        <div class="reference-warning mb-4"><span aria-hidden="true">&#9888;&#65039;</span> <span><?= e($notice) ?></span></div>
                    <?php endif; ?>
                    <?php if ($successMessage): ?>
                        <div class="mb-4 rounded-lg border border-emerald-500 bg-emerald-500/10 text-emerald-400 text-sm px-3 py-2"><?= e($successMessage) ?></div>
                    <?php endif; ?>
                    <?php if ($errors): ?>
                        <div class="mb-4 rounded-lg border border-red-500 bg-red-500/10 text-red-400 text-sm px-3 py-2"><?= implode('<br>', array_map('e', $errors)) ?></div>
                    <?php endif; ?>

                    <form method="post" autocomplete="off" class="space-y-4">
                        <?php if ($redirect !== ''): ?>
                            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                        <?php endif; ?>
                        <div>
                            <label for="loginEmail" class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                            <input type="email" id="loginEmail" name="email" required autocomplete="off"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                        </div>
                        <div>
                            <label for="loginPassword" class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                            <input type="password" id="loginPassword" name="password" required autocomplete="new-password"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                        </div>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-amber-600 text-slate-900 font-bold text-sm py-3 rounded-lg hover:from-amber-300 hover:to-amber-500 transition-colors">
                            ACCESS DASHBOARD <?php icon('arrow-right'); ?>
                        </button>
                    </form>
                </div>

                <div id="panelSignup" class="hidden">
                    <h2 class="text-xl font-bold mb-1">Create Account</h2>
                    <p class="text-sm text-slate-400 mb-5">Sign up to request documents, report problems, and post lost items.</p>

                    <form method="post" action="resident_register.php" autocomplete="off" class="space-y-4">
                        <?php if ($redirect !== ''): ?>
                            <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
                        <?php endif; ?>
                        <div>
                            <label for="signupName" class="block text-xs font-semibold text-slate-300 mb-1.5">Full Name</label>
                            <input type="text" id="signupName" name="full_name" required autocomplete="off"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                        </div>
                        <div>
                            <label for="signupEmail" class="block text-xs font-semibold text-slate-300 mb-1.5">Email Address</label>
                            <input type="email" id="signupEmail" name="email" required autocomplete="off"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                        </div>
                        <div>
                            <label for="signupPassword" class="block text-xs font-semibold text-slate-300 mb-1.5">Password</label>
                            <input type="password" id="signupPassword" name="password" required autocomplete="new-password"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                            <p class="text-[11px] text-slate-500 mt-1">At least 8 characters.</p>
                        </div>
                        <div>
                            <label for="signupConfirmPassword" class="block text-xs font-semibold text-slate-300 mb-1.5">Confirm Password</label>
                            <input type="password" id="signupConfirmPassword" name="confirm_password" required autocomplete="new-password"
                                class="w-full bg-slate-950/60 border border-slate-700 rounded-lg px-3.5 py-2.5 text-sm text-white placeholder-slate-500 outline-none focus:border-amber-400 focus:ring-2 focus:ring-amber-400/30 transition-all">
                        </div>
                        <button type="submit" class="w-full flex items-center justify-center gap-2 bg-gradient-to-r from-amber-400 to-amber-600 text-slate-900 font-bold text-sm py-3 rounded-lg hover:from-amber-300 hover:to-amber-500 transition-colors">
                            CREATE ACCOUNT <?php icon('arrow-right'); ?>
                        </button>
                    </form>
                </div>

                <a href="#" id="openLoginModal2" class="block text-center mt-5 text-xs font-semibold text-slate-500 hover:text-amber-400 transition-colors">Staff/Admin Login</a>
            </div>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/login_modal.php'; ?>

<script src="https://cdn.jsdelivr.net/npm/lucide@latest/dist/umd/lucide.min.js"></script>
<script src="assets/js/main.js"></script>
<script>
    if (window.lucide) { lucide.createIcons(); }
    document.documentElement.setAttribute('data-theme', 'dark');

    document.addEventListener('DOMContentLoaded', () => {
        const loginEmail = document.getElementById('loginEmail');
        const loginPassword = document.getElementById('loginPassword');
        if (loginEmail) loginEmail.value = '';
        if (loginPassword) loginPassword.value = '';
    });

    // Staff/Admin Access pill (top) also opens the same modal as the bottom link.
    const openModal2 = document.getElementById('openLoginModal2');
    if (openModal2) {
        openModal2.addEventListener('click', (e) => {
            e.preventDefault();
            document.getElementById('openLoginModal').click();
        });
    }

    // Login / Sign Up tab switcher
    const ACTIVE = ['bg-amber-500', 'text-slate-900'];
    const INACTIVE = ['text-slate-400', 'hover:text-white'];
    const tabBtnLogin = document.getElementById('tabBtnLogin');
    const tabBtnSignup = document.getElementById('tabBtnSignup');
    const panelLogin = document.getElementById('panelLogin');
    const panelSignup = document.getElementById('panelSignup');

    function setTab(tab) {
        const isLogin = tab === 'login';
        tabBtnLogin.classList.remove(...ACTIVE, ...INACTIVE);
        tabBtnSignup.classList.remove(...ACTIVE, ...INACTIVE);
        tabBtnLogin.classList.add(...(isLogin ? ACTIVE : INACTIVE));
        tabBtnSignup.classList.add(...(isLogin ? INACTIVE : ACTIVE));
        panelLogin.classList.toggle('hidden', !isLogin);
        panelSignup.classList.toggle('hidden', isLogin);
    }
    tabBtnLogin.addEventListener('click', () => setTab('login'));
    tabBtnSignup.addEventListener('click', () => setTab('signup'));
    setTab('login');

    function updateAuthClock() {
        const clockEl = document.getElementById('authClock');
        if (!clockEl) return;
        const formatted = new Intl.DateTimeFormat('en-PH', {
            timeZone: 'Asia/Manila',
            hour: '2-digit', minute: '2-digit', second: '2-digit', hour12: true,
        }).format(new Date());
        clockEl.lastChild.textContent = ' ' + formatted + ' (Manila)';
    }
    updateAuthClock();
    setInterval(updateAuthClock, 1000);
</script>
</body>
</html>
