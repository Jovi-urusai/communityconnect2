<?php require_once __DIR__ . '/../../config/resident_auth.php'; ?>
<script src="https://cdn.tailwindcss.com"></script>
<header class="sticky top-0 z-50 bg-[var(--surface-strong)] backdrop-blur-md border-b border-[var(--border)]">
    <div class="max-w-[1180px] mx-auto flex items-center justify-between gap-4 flex-wrap px-5 py-3.5">
        <div class="flex items-center gap-2.5">
            <div class="w-10 h-10 rounded-xl bg-amber-500 text-slate-950 font-bold flex items-center justify-center shrink-0">CC</div>
            <div>
                <h1 class="m-0 text-lg font-bold text-[var(--text)] leading-tight">Community<span class="text-amber-400">Connect</span></h1>
                <p class="m-0 text-xs text-[var(--text-muted)] leading-tight">Sto. Niño Barangay Hall, Eustaquio St. — Announcements &amp; Info</p>
            </div>
        </div>
        <div class="flex items-center gap-2 flex-wrap">
            <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] text-[var(--text-muted)] border border-[var(--border)]"><?php icon('eye'); ?> Public View</span>
            <button type="button" id="themeToggle" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--border)] text-[var(--text-muted)] hover:border-amber-500/50 transition-colors">
                <?php icon('sun', 'theme-icon-sun'); ?>
                <?php icon('moon', 'theme-icon-moon'); ?>
                <span id="themeToggleLabel">Light mode</span>
            </button>
            <?php if (resident_is_logged_in()): ?>
                <span class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] text-[var(--text-muted)] border border-[var(--border)]"><?php icon('user'); ?> Hi, <?= e($_SESSION['resident_name']) ?></span>
                <a href="resident_logout.php" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--border)] text-[var(--text-muted)] hover:border-amber-500/50 transition-colors"><?php icon('log-out'); ?> Log out</a>
            <?php else: ?>
                <a href="resident_login.php" class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-md transition-all"><?php icon('log-in'); ?> Resident Login</a>
                <a href="resident_register.php" class="inline-flex items-center gap-1.5 text-xs font-semibold px-3 py-1.5 rounded-full bg-[var(--surface)] border border-[var(--border)] text-[var(--text-muted)] hover:border-amber-500/50 transition-colors"><?php icon('user-plus'); ?> Sign Up</a>
            <?php endif; ?>
            <button type="button" id="openLoginModal" class="inline-flex items-center gap-1.5 text-xs font-bold px-4 py-2 rounded-xl bg-amber-500 hover:bg-amber-400 text-slate-950 shadow-md transition-all"><?php icon('shield'); ?> Staff/Admin Login</button>
        </div>
    </div>
</header>
