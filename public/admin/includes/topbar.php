<div class="admin-header">
    <h2><?= e($pageTitle ?? '') ?></h2>
    <div class="header-actions">
        <span class="badge-public"><?php icon('user-circle'); ?> Signed in as <?= e($adminUsername) ?></span>
        <button type="button" id="adminThemeToggle" class="btn">
            <?php icon('sun', 'theme-icon-sun'); ?>
            <?php icon('moon', 'theme-icon-moon'); ?>
            <span id="adminThemeToggleLabel">Dark mode</span>
        </button>
        <a href="logout.php" class="btn btn-danger"><?php icon('log-out'); ?> Log out</a>
    </div>
</div>
