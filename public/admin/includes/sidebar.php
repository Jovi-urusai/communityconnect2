<?php
/** @var array<string,string> $navItems Set by admin_layout_top.php before this file is included */
/** @var array<string,string> $navIcons Set by admin_layout_top.php before this file is included */
/** @var string $currentPage Set by admin_layout_top.php before this file is included */
?>
<div class="admin-sidebar">
    <div class="brand">
        <div class="brand-logo">CC</div>
        <div class="brand-text">
            <h1 style="font-size:15px;">CommunityConnect</h1>
            <p>Admin Panel</p>
        </div>
    </div>
    <nav class="admin-nav">
        <?php foreach ($navItems as $file => $label): ?>
            <a href="<?= e($file) ?>" class="<?= $currentPage === $file ? 'active' : '' ?>"><?php icon($navIcons[$file] ?? 'circle'); ?> <?= e($label) ?></a>
        <?php endforeach; ?>
    </nav>
</div>
