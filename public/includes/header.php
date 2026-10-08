<?php require_once __DIR__ . '/../../config/resident_auth.php'; ?>
<header class="site-header">
    <div class="header-inner">
        <div class="brand">
            <div class="brand-logo">CC</div>
            <div class="brand-text">
                <h1>Community<span class="brand-accent">Connect</span></h1>
                <p>Sto. Niño Barangay Hall, Eustaquio St. — Announcements &amp; Info</p>
            </div>
        </div>
        <div class="header-actions">
            <span class="badge-public"><?php icon('eye'); ?> Public View</span>
            <button type="button" id="themeToggle" class="btn">
                <?php icon('sun', 'theme-icon-sun'); ?>
                <?php icon('moon', 'theme-icon-moon'); ?>
                <span id="themeToggleLabel">Light mode</span>
            </button>
            <?php if (resident_is_logged_in()): ?>
                <span class="badge-public"><?php icon('user'); ?> Hi, <?= e($_SESSION['resident_name']) ?></span>
                <a href="resident_logout.php" class="btn"><?php icon('log-out'); ?> Log out</a>
            <?php else: ?>
                <a href="resident_login.php" class="btn btn-primary"><?php icon('log-in'); ?> Resident Login</a>
                <a href="resident_register.php" class="btn"><?php icon('user-plus'); ?> Sign Up</a>
            <?php endif; ?>
            <button type="button" id="openLoginModal" class="btn btn-primary"><?php icon('shield'); ?> Staff/Admin Login</button>
        </div>
    </div>
</header>
