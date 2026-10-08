<?php
// Resident (public account) session handling — completely separate from
// config/auth.php, which is for staff/admin accounts only. Residents use
// $_SESSION['resident_id'] / 'resident_name' / 'resident_email'; admins use
// $_SESSION['admin_id'] / 'admin_username'. The two never overlap, and a
// resident account can never reach the admin panel (require_login() there
// only checks admin_id).

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function resident_is_logged_in(): bool
{
    return !empty($_SESSION['resident_id']);
}

/**
 * Gates a resident-only page. If not logged in, remembers the page the
 * visitor was trying to reach (so login can send them back) and redirects
 * to the login page with a friendly notice.
 */
function require_resident_login(): void
{
    if (!resident_is_logged_in()) {
        $_SESSION['resident_login_notice'] = 'Please log in to continue.';
        $redirectTo = basename($_SERVER['SCRIPT_NAME']);
        header('Location: resident_login.php?redirect=' . urlencode($redirectTo));
        exit;
    }
}
