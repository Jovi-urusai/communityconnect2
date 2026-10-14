<?php
$currentPage = basename($_SERVER['SCRIPT_NAME']);
$navItems = [
    ['index.php', 'home', 'Home', ['index.php']],
    ['category.php', 'layout-grid', 'Browse by Category', ['category.php']],
    ['community_feed.php', 'messages-square', 'Community Feed', ['community_feed.php', 'community_post_create.php']],
    ['lost_found.php', 'package-search', 'Lost Item', ['lost_found.php']],
    ['resolved_reports.php', 'circle-check-big', 'Resolved Reports', ['resolved_reports.php']],
    ['track_request.php', 'search', 'Track My Request', ['track_request.php']],
];
$navActiveClass = 'flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-bold whitespace-nowrap bg-amber-500/20 text-amber-400 border-l-4 border-amber-500 shadow-lg shadow-amber-500/10';
$navInactiveClass = 'flex items-center gap-2.5 px-3.5 py-2.5 rounded-lg text-sm font-semibold whitespace-nowrap text-[var(--text-muted)] border-l-4 border-transparent hover:bg-[var(--surface-hover)] hover:text-[var(--text)] transition-colors';
?>
<aside class="w-full lg:w-[220px] shrink-0 lg:sticky lg:top-[65px] self-start p-4 bg-[var(--surface-strong)] backdrop-blur-md border-b lg:border-b-0 lg:border-r border-[var(--border)]">
    <nav class="flex flex-row lg:flex-col gap-1 overflow-x-auto lg:overflow-visible" id="publicNav">
        <?php foreach ($navItems as [$href, $iconName, $label, $matchPages]): ?>
            <a href="<?= e($href) ?>" class="<?= in_array($currentPage, $matchPages, true) ? $navActiveClass : $navInactiveClass ?>">
                <?php icon($iconName); ?> <?= e($label) ?>
            </a>
        <?php endforeach; ?>
    </nav>
</aside>
