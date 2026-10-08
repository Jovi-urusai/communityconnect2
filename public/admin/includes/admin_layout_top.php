<?php
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../../config/auth.php';
require_login();

$adminUsername = $_SESSION['admin_username'] ?? 'admin';
$currentPage = basename($_SERVER['SCRIPT_NAME']);

$navItems = [
    'dashboard.php' => 'Dashboard',
    'manage_announcements.php' => 'Manage Announcements',
    'manage_categories.php' => 'Manage Categories',
    'lost_found_items.php' => 'Lost Item',
    'advisory_banner.php' => 'Advisory Banner',
    'document_requests.php' => 'Document Requests',
    'problem_reports.php' => 'Problem Reports',
    'community_posts.php' => 'Community Posts',
    'residents.php' => 'Residents',
    'reports.php' => 'Reports',
];

$navIcons = [
    'dashboard.php' => 'layout-dashboard',
    'manage_announcements.php' => 'megaphone',
    'manage_categories.php' => 'tags',
    'lost_found_items.php' => 'package-search',
    'advisory_banner.php' => 'alert-triangle',
    'document_requests.php' => 'file-text',
    'problem_reports.php' => 'flag',
    'community_posts.php' => 'messages-square',
    'residents.php' => 'users',
    'reports.php' => 'bar-chart-3',
];
