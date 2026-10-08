<?php $currentPage = basename($_SERVER['SCRIPT_NAME']); ?>
<aside class="public-sidebar">
    <nav class="public-nav" id="publicNav">
        <a href="index.php" class="<?= $currentPage === 'index.php' ? 'active' : '' ?>"><?php icon('home'); ?> Home</a>
        <a href="category.php" class="<?= $currentPage === 'category.php' ? 'active' : '' ?>"><?php icon('layout-grid'); ?> Browse by Category</a>
        <a href="community_feed.php" class="<?= $currentPage === 'community_feed.php' || $currentPage === 'community_post_create.php' ? 'active' : '' ?>"><?php icon('messages-square'); ?> Community Feed</a>
        <a href="lost_found.php" class="<?= $currentPage === 'lost_found.php' ? 'active' : '' ?>"><?php icon('package-search'); ?> Lost Item</a>
        <a href="resolved_reports.php" class="<?= $currentPage === 'resolved_reports.php' ? 'active' : '' ?>"><?php icon('circle-check-big'); ?> Resolved Reports</a>
        <a href="track_request.php" class="<?= $currentPage === 'track_request.php' ? 'active' : '' ?>"><?php icon('search'); ?> Track My Request</a>
    </nav>
</aside>
